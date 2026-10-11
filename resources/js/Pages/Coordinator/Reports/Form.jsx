import { Head, useForm } from '@inertiajs/react';
import { useMemo, useRef } from 'react';
import Layout from '../../../Components/Layout';
import { Button, Card, Field, MemberSelect } from '../../../Components/ui';

const minutes = (a, b) => {
    if (!a || !b) return null;
    const [ah, am] = a.split(':').map(Number);
    const [bh, bm] = b.split(':').map(Number);
    const d = bh * 60 + bm - (ah * 60 + am);
    return d < 0 ? d + 1440 : d;
};

function RowActions({ onRemove }) {
    return <button type="button" onClick={onRemove} className="px-2 text-lg leading-none text-slate-400 hover:text-red-600" title="Remove">×</button>;
}

export default function Form({ report, serviceTypes, workers, severities, incidentStatuses, broadcastStatuses }) {
    const { data, setData, post, put, processing, errors } = useForm(report);
    const typeDefaults = useRef(serviceTypes.find((t) => t.id === report.service_type_id) ?? {});
    const isNew = !report.id;

    const set = (key) => (e) => setData(key, e.target.value);
    const setRow = (key, i, field, value) =>
        setData(key, data[key].map((row, idx) => (idx === i ? { ...row, [field]: value } : row)));
    const addRow = (key, row) => setData(key, [...data[key], row]);
    const removeRow = (key, i) => setData(key, data[key].filter((_, idx) => idx !== i));
    const err = (path) => errors[path];

    const total = useMemo(() => data.viewership.reduce((s, v) => s + (parseInt(v.viewers, 10) || 0), 0), [data.viewership]);
    const mins = minutes(data.start_time, data.end_time);
    const duration = mins === null ? '—' : `${Math.floor(mins / 60)} hr ${mins % 60} min`;

    async function changeServiceType(e) {
        const id = Number(e.target.value);
        const next = serviceTypes.find((t) => t.id === id);
        const prev = typeDefaults.current;
        const patch = { service_type_id: id };
        // only swap boilerplate text the coordinator has not edited
        if (!data.summary || data.summary === prev.default_summary) patch.summary = next.default_summary ?? '';
        if (!data.conclusion || data.conclusion === prev.default_conclusion) patch.conclusion = next.default_conclusion ?? '';
        try {
            const res = await fetch(`/coordinator/service-types/${id}/tasks`, { headers: { Accept: 'application/json' } });
            const fresh = await res.json();
            const keep = Object.fromEntries(data.tasks.map((t) => [t.number, t]));
            patch.tasks = fresh.map((t) => ({ ...t, handled_by: keep[t.number]?.handled_by ?? '', remarks: keep[t.number]?.remarks || t.remarks }));
        } catch { /* keep existing tasks */ }
        typeDefaults.current = next;
        setData((d) => ({ ...d, ...patch }));
    }

    const present = new Set(data.attendees.map((a) => a.name));
    const toggleWorker = (w) =>
        setData('attendees', present.has(w.name)
            ? data.attendees.filter((a) => a.name !== w.name)
            : [...data.attendees, { name: w.name, role: w.role ?? '' }]);

    function save(e) {
        e.preventDefault();
        isNew ? post('/coordinator/reports') : put(`/coordinator/reports/${report.id}`);
    }

    const errorCount = Object.keys(errors).length;

    return (
        <Layout title={isNew ? 'New service report' : 'Edit service report'}
            actions={<Button href={isNew ? '/coordinator/reports' : `/coordinator/reports/${report.id}`} variant="ghost">Cancel</Button>}>
            <Head title={isNew ? 'New report' : 'Edit report'} />
            <form onSubmit={save} className="space-y-5 pb-24">
                {errorCount > 0 && (
                    <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">
                        Please fix {errorCount} highlighted {errorCount === 1 ? 'problem' : 'problems'} below.
                    </div>
                )}
                {report.status === 'returned' && report.admin_note && (
                    <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><b>Admin note:</b> {report.admin_note}</div>
                )}

                <Card title="Service details">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Field label="Service type" error={err('service_type_id')}>
                            <select value={data.service_type_id ?? ''} onChange={changeServiceType}>
                                {serviceTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                            </select>
                        </Field>
                        <Field label="Date" error={err('service_date')}>
                            <input type="date" value={data.service_date} onChange={set('service_date')} />
                        </Field>
                        <Field label="Report prepared by" error={err('prepared_by')}>
                            <MemberSelect members={workers} value={data.prepared_by} onChange={(v) => setData('prepared_by', v)} placeholder="Select member…" />
                        </Field>
                        <Field label="Service start time" error={err('start_time')}>
                            <input type="time" value={data.start_time} onChange={set('start_time')} />
                        </Field>
                        <Field label="Service end time" error={err('end_time')}>
                            <input type="time" value={data.end_time} onChange={set('end_time')} />
                        </Field>
                        <Field label="Total duration">
                            <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">{duration}</div>
                        </Field>
                        <Field label="Broadcast status" error={err('broadcast_status')} className="sm:col-span-2 lg:col-span-1">
                            <input value={data.broadcast_status} onChange={set('broadcast_status')} list="broadcast-statuses" />
                        </Field>
                    </div>
                </Card>

                <Card title="1. Service report summary" hint="Pre-filled from the service type; edit it to describe this service.">
                    <textarea rows={5} value={data.summary} onChange={set('summary')} />
                    {err('summary') && <p className="mt-1 text-xs text-red-600">{err('summary')}</p>}
                </Card>

                <Card title="2. Service tasks & assignments" hint="Pick who handled each task. Add more than one person if needed.">
                    <div className="space-y-2">
                        {data.tasks.map((t, i) => (
                            <div key={i} className="grid items-start gap-2 rounded-lg border border-slate-100 p-2.5 md:grid-cols-[3rem_1.4fr_1fr_1.4fr_auto]">
                                <input value={t.number} onChange={(e) => setRow('tasks', i, 'number', e.target.value)} aria-label="Number" />
                                <div>
                                    <input value={t.title} onChange={(e) => setRow('tasks', i, 'title', e.target.value)} aria-label="Task" />
                                    {err(`tasks.${i}.title`) && <p className="text-xs text-red-600">{err(`tasks.${i}.title`)}</p>}
                                </div>
                                <MemberSelect multiple members={workers} value={t.handled_by} placeholder="Handled by…"
                                    onChange={(v) => setRow('tasks', i, 'handled_by', v)} />
                                <input value={t.remarks ?? ''} placeholder="Output / remarks"
                                    onChange={(e) => setRow('tasks', i, 'remarks', e.target.value)} />
                                <RowActions onRemove={() => removeRow('tasks', i)} />
                            </div>
                        ))}
                    </div>
                    <Button variant="secondary" className="mt-3" onClick={() => addRow('tasks', { number: '', title: '', handled_by: '', remarks: '' })}>+ Add task</Button>
                </Card>

                <Card title="3. Online viewership">
                    <div className="space-y-2">
                        {data.viewership.map((v, i) => (
                            <div key={i} className="grid grid-cols-[1fr_9rem_auto] gap-2">
                                <input value={v.platform} onChange={(e) => setRow('viewership', i, 'platform', e.target.value)} aria-label="Platform" />
                                <div>
                                    <input type="number" min="0" value={v.viewers ?? ''} placeholder="Viewers"
                                        onChange={(e) => setRow('viewership', i, 'viewers', e.target.value)} />
                                    {err(`viewership.${i}.viewers`) && <p className="text-xs text-red-600">{err(`viewership.${i}.viewers`)}</p>}
                                </div>
                                <RowActions onRemove={() => removeRow('viewership', i)} />
                            </div>
                        ))}
                    </div>
                    <div className="mt-3 flex items-center justify-between">
                        <Button variant="secondary" onClick={() => addRow('viewership', { platform: '', viewers: '' })}>+ Other platform</Button>
                        <div className="text-sm">Total online viewership: <b className="text-brand-700">{total.toLocaleString()}</b></div>
                    </div>
                </Card>

                <Card title="4. Workers’ attendance" hint={`Tap to mark who was present. ${data.attendees.length} present.`}>
                    <div className="flex flex-wrap gap-2">
                        {workers.map((w) => (
                            <button type="button" key={w.name} onClick={() => toggleWorker(w)}
                                className={`rounded-full border px-3 py-1.5 text-sm ${present.has(w.name)
                                    ? 'border-brand-700 bg-brand-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-500'}`}>
                                {w.name}{w.role ? ` · ${w.role}` : ''}
                            </button>
                        ))}
                    </div>
                    {data.attendees.filter((a) => !workers.some((w) => w.name === a.name)).map((a) => (
                        <div key={a.name} className="mt-2 flex items-center gap-2 text-sm">
                            <span className="rounded-full bg-brand-100 px-3 py-1">{a.name}</span>
                            <button type="button" className="text-slate-400 hover:text-red-600" onClick={() => setData('attendees', data.attendees.filter((x) => x !== a))}>remove</button>
                        </div>
                    ))}
                    <div className="mt-3 flex max-w-sm gap-2">
                        <input id="guest" placeholder="Someone not on the roster…"
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    const v = e.target.value.trim();
                                    if (v && !present.has(v)) addRow('attendees', { name: v, role: '' });
                                    e.target.value = '';
                                }
                            }} />
                        <span className="self-center whitespace-nowrap text-xs text-slate-500">Enter to add</span>
                    </div>
                </Card>

                <Card title="5. Operational observations" hint="Short headed points, e.g. Stability, Audio & video, Engagement, Issues.">
                    <div className="space-y-2">
                        {data.observations.map((o, i) => (
                            <div key={i} className="grid gap-2 md:grid-cols-[10rem_1fr_auto]">
                                <input value={o.label ?? ''} placeholder="Heading" onChange={(e) => setRow('observations', i, 'label', e.target.value)} />
                                <div>
                                    <input value={o.text} placeholder="Observation" onChange={(e) => setRow('observations', i, 'text', e.target.value)} />
                                    {err(`observations.${i}.text`) && <p className="text-xs text-red-600">{err(`observations.${i}.text`)}</p>}
                                </div>
                                <RowActions onRemove={() => removeRow('observations', i)} />
                            </div>
                        ))}
                    </div>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <Button variant="secondary" onClick={() => addRow('observations', { label: '', text: '' })}>+ Add observation</Button>
                        {['Stability', 'Audio & video', 'Engagement', 'Issues'].filter((l) => !data.observations.some((o) => o.label === l)).map((l) => (
                            <Button key={l} variant="ghost" onClick={() => addRow('observations', { label: l, text: '' })}>+ {l}</Button>
                        ))}
                    </div>
                </Card>

                <Card title="6. Workspace status" hint="Was the workspace left clean and tidy after the service?">
                    <div className="flex flex-wrap gap-2">
                        {[[true, 'Yes, it is clean'], [false, 'No, it is not clean']].map(([val, label]) => (
                            <button type="button" key={label} onClick={() => setData('workspace_clean', val)}
                                className={`rounded-full border px-4 py-1.5 text-sm font-medium ${data.workspace_clean === val
                                    ? (val ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-red-600 bg-red-600 text-white')
                                    : 'border-slate-300 bg-white text-slate-700 hover:border-brand-500'}`}>
                                {label}
                            </button>
                        ))}
                    </div>
                    {err('workspace_clean') && <p className="mt-1 text-xs text-red-600">Please say whether the workspace is clean.</p>}
                    <div className="mt-3">
                        <label>Remarks (optional)</label>
                        <input value={data.workspace_notes ?? ''} onChange={set('workspace_notes')}
                            placeholder={data.workspace_clean === false ? 'What needs cleaning up?' : 'e.g. all cables packed, desks cleared'} />
                    </div>
                </Card>

                <Card title="7. Incident report" hint="Leave empty if nothing went wrong.">
                    <div className="space-y-3">
                        {data.incidents.map((n, i) => (
                            <div key={i} className="grid gap-2 rounded-lg border border-slate-100 p-2.5 md:grid-cols-[6rem_1.4fr_8rem_1.4fr_9rem_auto]">
                                <input type="time" value={n.time ?? ''} onChange={(e) => setRow('incidents', i, 'time', e.target.value)} />
                                <div>
                                    <input value={n.incident ?? ''} placeholder="Incident" onChange={(e) => setRow('incidents', i, 'incident', e.target.value)} />
                                    {err(`incidents.${i}.incident`) && <p className="text-xs text-red-600">{err(`incidents.${i}.incident`)}</p>}
                                </div>
                                <select value={n.severity ?? ''} onChange={(e) => setRow('incidents', i, 'severity', e.target.value)}>
                                    <option value="">Severity</option>
                                    {severities.map((s) => <option key={s}>{s}</option>)}
                                </select>
                                <input value={n.action_taken ?? ''} placeholder="Action taken" onChange={(e) => setRow('incidents', i, 'action_taken', e.target.value)} />
                                <select value={n.status ?? ''} onChange={(e) => setRow('incidents', i, 'status', e.target.value)}>
                                    <option value="">Status</option>
                                    {incidentStatuses.map((s) => <option key={s}>{s}</option>)}
                                </select>
                                <RowActions onRemove={() => removeRow('incidents', i)} />
                            </div>
                        ))}
                    </div>
                    <Button variant="secondary" className="mt-3"
                        onClick={() => addRow('incidents', { time: '', incident: '', severity: '', action_taken: '', status: '' })}>+ Add incident</Button>
                </Card>

                <Card title="8. Conclusion">
                    <textarea rows={4} value={data.conclusion} onChange={set('conclusion')} />
                </Card>

                <datalist id="broadcast-statuses">{broadcastStatuses.map((s) => <option key={s} value={s} />)}</datalist>

                <div className="fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur md:left-60">
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 md:px-4">
                        <span className="text-xs text-slate-500">Saved as a draft. Submit from the preview when ready.</span>
                        <Button type="submit" disabled={processing}>{processing ? 'Saving…' : 'Save & preview'}</Button>
                    </div>
                </div>
            </form>
        </Layout>
    );
}
