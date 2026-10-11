import { Head, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import { Card } from '../../../Components/ui';
import { ReportRows } from '../Dashboard';

export default function Index({ reports, filters, serviceTypes, coordinators }) {
    const apply = (key, value) =>
        router.get('/admin/reports', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });

    return (
        <Layout title="All reports">
            <Head title="Reports" />
            <Card className="mb-4">
                <div className="grid gap-3 sm:grid-cols-3">
                    <div><label>Status</label>
                        <select value={filters.status ?? ''} onChange={(e) => apply('status', e.target.value)}>
                            <option value="">All</option>
                            <option value="submitted">Awaiting review</option>
                            <option value="approved">Approved</option>
                            <option value="returned">Returned</option>
                            <option value="draft">Draft</option>
                        </select></div>
                    <div><label>Service type</label>
                        <select value={filters.service_type_id ?? ''} onChange={(e) => apply('service_type_id', e.target.value)}>
                            <option value="">All</option>
                            {serviceTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                        </select></div>
                    <div><label>Coordinator</label>
                        <select value={filters.user_id ?? ''} onChange={(e) => apply('user_id', e.target.value)}>
                            <option value="">All</option>
                            {coordinators.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                        </select></div>
                </div>
            </Card>
            <Card><ReportRows rows={reports} /></Card>
        </Layout>
    );
}
