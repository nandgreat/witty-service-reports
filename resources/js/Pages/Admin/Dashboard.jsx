import { Head, Link } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import { Card, StatusBadge, Table } from '../../Components/ui';

export default function Dashboard({ counts, recent }) {
    const tiles = [
        ['Awaiting review', counts.awaiting, 'submitted', 'text-amber-700'],
        ['Approved', counts.approved, 'approved', 'text-emerald-700'],
        ['Returned', counts.returned, 'returned', 'text-red-600'],
        ['Drafts', counts.drafts, 'draft', 'text-slate-700'],
    ];
    return (
        <Layout title="Dashboard">
            <Head title="Dashboard" />
            <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                {tiles.map(([label, n, status, color]) => (
                    <Link key={label} href={`/admin/reports?status=${status}`} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-brand-500">
                        <div className={`text-3xl font-extrabold ${color}`}>{n}</div>
                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
                    </Link>
                ))}
            </div>
            <Card title="Recent reports" hint={`${counts.coordinators} active service coordinators`}>
                <ReportRows rows={recent} />
            </Card>
        </Layout>
    );
}

export function ReportRows({ rows }) {
    return (
        <Table head={['Service', 'Date', 'Prepared by', 'Viewers', 'Status']} empty={rows.length ? null : 'No reports yet.'}>
            {rows.map((r) => (
                <tr key={r.id} className="hover:bg-slate-50">
                    <td className="px-3 py-2.5 font-medium"><Link href={`/admin/reports/${r.id}`} className="text-brand-700 hover:underline">{r.service_type}</Link></td>
                    <td className="px-3 py-2.5">{r.service_date}</td>
                    <td className="px-3 py-2.5">
                        {r.prepared_by || <span className="text-slate-400">Not set</span>}
                        {r.prepared_by && r.coordinator !== r.prepared_by && <div className="text-xs text-slate-500">via {r.coordinator}</div>}
                    </td>
                    <td className="px-3 py-2.5">{r.total_viewers.toLocaleString()}</td>
                    <td className="px-3 py-2.5"><StatusBadge status={r.status} /></td>
                </tr>
            ))}
        </Table>
    );
}
