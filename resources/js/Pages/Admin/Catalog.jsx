import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';
import { Button, Card, Field } from '../../Components/ui';

// Field definitions per catalogue: [key, label, input type, span]
const FIELDS = {
    'service-types': [
        ['name', 'Name', 'text'],
        ['short_name', 'Short name (header & file name)', 'text'],
        ['default_summary', 'Default summary text', 'textarea'],
        ['default_conclusion', 'Default conclusion text', 'textarea'],
    ],
    tasks: [
        ['number', 'No.', 'text'],
        ['title', 'Task', 'text'],
        ['default_remarks', 'Default remarks', 'text'],
        ['service_type_id', 'Service type', 'service-type'],
        ['sort_order', 'Order', 'number'],
    ],
    platforms: [
        ['name', 'Platform', 'text'],
        ['sort_order', 'Order', 'number'],
    ],
    workers: [
        ['name', 'Name', 'text'],
        ['role', 'Role (e.g. HOD)', 'text'],
        ['church_branch', 'Church branch', 'branch'],
    ],
};

const blank = (fields) => ({ ...Object.fromEntries(fields.map(([k, , t]) => [k, t === 'number' ? 0 : ''])), is_active: true });

function Editor({ catalog, fields, item, serviceTypes, branches = [], onDone }) {
    const form = useForm(item ? { ...blank(fields), ...item, service_type_id: item.service_type_id ?? '' } : blank(fields));
    const url = `/admin/catalog/${catalog}`;
    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { form.reset(); onDone?.(); } };
        const payload = (d) => ({ ...d, service_type_id: d.service_type_id === '' ? null : d.service_type_id });
        form.transform(payload);
        item ? form.put(`${url}/${item.id}`, opts) : form.post(url, opts);
    };
    return (
        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-2">
            {fields.map(([key, label, type]) => (
                <Field key={key} label={label} error={form.errors[key]} className={type === 'textarea' ? 'sm:col-span-2' : ''}>
                    {type === 'textarea' ? (
                        <textarea rows={3} value={form.data[key] ?? ''} onChange={(e) => form.setData(key, e.target.value)} />
                    ) : type === 'service-type' ? (
                        <select value={form.data[key] ?? ''} onChange={(e) => form.setData(key, e.target.value)}>
                            <option value="">All service types</option>
                            {serviceTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                        </select>
                    ) : type === 'branch' ? (
                        <>
                            <input type="text" list="church-branches" placeholder="e.g. Abuja, Lagos…" value={form.data[key] ?? ''} onChange={(e) => form.setData(key, e.target.value)} />
                            <datalist id="church-branches">{branches.map((b) => <option key={b} value={b} />)}</datalist>
                        </>
                    ) : (
                        <input type={type} value={form.data[key] ?? ''} onChange={(e) => form.setData(key, e.target.value)} />
                    )}
                </Field>
            ))}
            <label className="flex items-center gap-2 text-sm font-normal normal-case tracking-normal text-slate-700 sm:col-span-2">
                <input type="checkbox" checked={!!form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} /> Active
            </label>
            <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={form.processing}>{item ? 'Save changes' : 'Add'}</Button>
                {item && <Button variant="ghost" onClick={onDone}>Cancel</Button>}
            </div>
        </form>
    );
}

export default function Catalog({ catalog, title, hint, items, serviceTypes, branches = [] }) {
    const [editing, setEditing] = useState(null);
    const [adding, setAdding] = useState(false);
    const fields = FIELDS[catalog];
    const { errors } = usePage().props;
    const typeName = (id) => serviceTypes.find((t) => t.id === id)?.name ?? 'All';
    const primary = fields[0][0];

    return (
        <Layout title={title} actions={<Button onClick={() => { setAdding(!adding); setEditing(null); }}>{adding ? 'Close' : '+ Add'}</Button>}>
            <Head title={title} />
            <p className="mb-4 text-sm text-slate-600">{hint}</p>
            {errors?.delete && <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{errors.delete}</div>}
            {adding && <Card title={`Add to ${title.toLowerCase()}`} className="mb-4"><Editor key="new" {...{ catalog, fields, serviceTypes, branches }} onDone={() => setAdding(false)} /></Card>}
            <div className="space-y-2">
                {items.map((it) => (
                    <Card key={it.id} className={it.is_active ? '' : 'opacity-60'}>
                        {editing === it.id ? (
                            <Editor {...{ catalog, fields, serviceTypes, branches }} item={it} onDone={() => setEditing(null)} />
                        ) : (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="font-semibold text-slate-900">
                                        {catalog === 'tasks' && <span className="mr-2 rounded bg-brand-100 px-1.5 py-0.5 text-xs text-brand-700">{it.number}</span>}
                                        {catalog === 'tasks' ? it.title : it[primary]}
                                        {!it.is_active && <span className="ml-2 text-xs font-normal text-slate-500">(inactive)</span>}
                                    </div>
                                    <div className="truncate text-xs text-slate-500">
                                        {catalog === 'service-types' && it.short_name}
                                        {catalog === 'tasks' && `Applies to: ${typeName(it.service_type_id)}`}
                                        {catalog === 'workers' && [it.role || 'Worker', it.church_branch].filter(Boolean).join(' · ')}
                                        {catalog === 'platforms' && `Order ${it.sort_order}`}
                                    </div>
                                </div>
                                <div className="flex gap-3 text-sm">
                                    <button className="font-semibold text-brand-700" onClick={() => { setEditing(it.id); setAdding(false); }}>Edit</button>
                                    <button className={it.is_active ? 'text-amber-700' : 'font-semibold text-emerald-700'}
                                        onClick={() => router.patch(`/admin/catalog/${catalog}/${it.id}/toggle`, {}, { preserveScroll: true })}>
                                        {it.is_active ? 'Disable' : 'Enable'}
                                    </button>
                                    <button className="text-red-600" onClick={() => confirm(`Remove “${it[primary]}”?`) && router.delete(`/admin/catalog/${catalog}/${it.id}`, { preserveScroll: true })}>Delete</button>
                                </div>
                            </div>
                        )}
                    </Card>
                ))}
                {items.length === 0 && <p className="py-8 text-center text-sm text-slate-500">Nothing here yet.</p>}
            </div>
        </Layout>
    );
}
