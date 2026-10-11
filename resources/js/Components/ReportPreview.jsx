const n = (v) => (v === null || v === '' || v === undefined ? '' : Number(v).toLocaleString());
const time = (t) => {
    if (!t) return '—';
    const [h, m] = t.split(':').map(Number);
    return `${((h + 11) % 12) + 1}:${String(m).padStart(2, '0')} ${h >= 12 ? 'PM' : 'AM'}`;
};

function H({ children }) {
    return <h3 className="mb-2 mt-7 text-base font-bold text-brand-700">{children}</h3>;
}

function Grid({ head, rows, boldLast }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full border-collapse text-sm">
                <thead>
                    <tr>{head.map((h) => <th key={h} className="border border-slate-300 bg-brand-700 px-2.5 py-1.5 text-left font-semibold text-white">{h}</th>)}</tr>
                </thead>
                <tbody>
                    {rows.map((r, i) => (
                        <tr key={i} className={boldLast && i === rows.length - 1 ? 'bg-brand-50 font-bold' : ''}>
                            {r.map((c, j) => <td key={j} className="border border-slate-300 px-2.5 py-1.5 align-top">{c}</td>)}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

const paras = (t) => (t || '').split(/\n+/).filter((p) => p.trim());

/** On-screen replica of the generated Word document. */
export default function ReportPreview({ report: r }) {
    const info = [
        ['Date', r.service_date_label],
        ['Service', r.service_type],
        ['Service start time', time(r.start_time)],
        ['Service end time', time(r.end_time)],
        ['Total duration', r.duration || '—'],
        ['Broadcast status', r.broadcast_status],
        ['Report prepared by', r.prepared_by],
    ];
    return (
        <article className="mx-auto max-w-[820px] rounded-xl border border-slate-200 bg-white p-6 text-sm leading-relaxed shadow-sm md:p-10">
            <div className="text-center">
                <img src="/img/logo.png" alt="" className="mx-auto h-16 w-16" />
                <div className="mt-1 text-2xl font-extrabold text-brand-700">WITTY INVENTIONS</div>
                <div className="text-base font-bold text-slate-600">SERVICE REPORT</div>
                <div className="text-sm uppercase text-slate-600">{r.service_type}</div>
            </div>

            <table className="mt-5 w-full border-collapse">
                <tbody>
                    {info.map(([k, v]) => (
                        <tr key={k}>
                            <td className="w-2/5 border border-slate-300 bg-brand-50 px-2.5 py-1.5 font-semibold text-brand-700">{k}</td>
                            <td className="border border-slate-300 px-2.5 py-1.5">{v}</td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <H>1. Service Report Summary</H>
            {paras(r.summary).map((p, i) => <p key={i} className="mb-2">{p}</p>)}

            <H>2. Service Tasks &amp; Assignments</H>
            <Grid head={['#', 'Service task', 'Handled by', 'Output / remarks']}
                rows={r.tasks.map((t) => [t.number, t.title, t.handled_by, t.remarks])} />

            <H>3. Online Viewership</H>
            <Grid head={['Platform', 'Viewers']} boldLast
                rows={[...r.viewership.map((v) => [v.platform, n(v.viewers)]), ['Total online viewership', n(r.total_viewers)]]} />

            <H>4. Workers’ Attendance</H>
            <p>Present: {r.attendees.length} workers. Duties are listed under Service Tasks &amp; Assignments.</p>
            <ul className="mt-1 list-disc pl-6">
                {r.attendees.map((a, i) => <li key={i}>{a.name}{a.role ? ` (${a.role})` : ''}</li>)}
            </ul>

            <H>5. Operational Observations</H>
            <ul className="list-disc pl-6">
                {r.observations.map((o, i) => (
                    <li key={i} className="mb-1">{o.label && <b>{o.label.replace(/\.\s*$/, '')}. </b>}{o.text}</li>
                ))}
            </ul>

            <H>6. Workspace Status</H>
            <p>
                Workspace left clean:{' '}
                {r.workspace_clean === null || r.workspace_clean === undefined
                    ? <b>Not recorded</b>
                    : r.workspace_clean
                        ? <b className="text-brand-700">Yes – the workspace is clean</b>
                        : <b className="text-red-700">No – the workspace is not clean</b>}
            </p>
            {r.workspace_notes && <p className="mt-1">Remarks: {r.workspace_notes}</p>}

            <H>7. Incident Report</H>
            <p className="mb-2">Incidents encountered during the service, the response taken and the current status.</p>
            <Grid head={['Time', 'Incident', 'Severity', 'Action taken', 'Status']}
                rows={r.incidents.length ? r.incidents.map((i) => [i.time, i.incident, i.severity, i.action_taken, i.status]) : [['', 'No incidents recorded', '', '', '']]} />

            <H>8. Conclusion</H>
            {paras(r.conclusion).map((p, i) => <p key={i} className="mb-2">{p}</p>)}
        </article>
    );
}
