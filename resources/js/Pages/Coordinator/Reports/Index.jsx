import { Head, Link, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import { Button, Card, StatusBadge, Table } from '../../../Components/ui';

export default function Index({ reports }) {
    return (
        <Layout title="My reports" actions={<Button href="/coordinator/reports/create">+ New report</Button>}>
            <Head title="My reports" />
            <Card>
                <Table head={['Service', 'Date', 'Prepared by', 'Online viewers', 'Status', '']} empty={reports.length ? null : 'No reports yet. Start with “New report”.'}>
                    {reports.map((r) => (
                        <tr key={r.id} className="hover:bg-slate-50">
                            <td className="px-3 py-2.5 font-medium">
                                <Link href={`/coordinator/reports/${r.id}`} className="text-brand-700 hover:underline">{r.service_type}</Link>
                                {r.status === 'returned' && r.admin_note && (
                                    <div className="mt-0.5 text-xs font-normal text-red-600">Admin note: {r.admin_note}</div>
                                )}
                            </td>
                            <td className="px-3 py-2.5">{r.service_date}</td>
                            <td className="px-3 py-2.5">{r.prepared_by || <span className="text-slate-400">Not set</span>}</td>
                            <td className="px-3 py-2.5">{r.total_viewers.toLocaleString()}</td>
                            <td className="px-3 py-2.5"><StatusBadge status={r.status} /></td>
                            <td className="whitespace-nowrap px-3 py-2.5 text-right text-xs">
                                {r.editable && <Link href={`/coordinator/reports/${r.id}/edit`} className="mr-3 font-semibold text-brand-700">Edit</Link>}
                                <a href={`/coordinator/reports/${r.id}/download`} className="mr-3 font-semibold text-brand-700">Word</a>
                                <button className="text-slate-500 hover:text-brand-700"
                                    onClick={() => router.post(`/coordinator/reports/${r.id}/duplicate`)}>Duplicate</button>
                            </td>
                        </tr>
                    ))}
                </Table>
            </Card>
        </Layout>
    );
}
