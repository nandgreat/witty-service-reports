import { Head, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import ReportPreview from '../../../Components/ReportPreview';
import { Button, StatusBadge } from '../../../Components/ui';

export default function Show({ report, editable }) {
    const base = `/coordinator/reports/${report.id}`;
    return (
        <Layout
            title={<span className="flex items-center gap-3">{report.service_type} · {report.service_date_label} <StatusBadge status={report.status} /></span>}
            actions={<>
                <Button href="/coordinator/reports" variant="ghost">← All reports</Button>
                {editable && <Button href={`${base}/edit`} variant="secondary">Edit</Button>}
                <Button variant="secondary" onClick={() => router.post(`${base}/duplicate`)}>Duplicate</Button>
                <Button as="a" href={`${base}/download`} variant={editable ? 'secondary' : 'primary'}>Download .docx</Button>
                {editable && <Button onClick={() => confirm('Submit this report to admin? You will not be able to edit it unless it is returned.') && router.post(`${base}/submit`)}>Submit to admin</Button>}
            </>}>
            <Head title="Report" />
            {report.status === 'returned' && report.admin_note && (
                <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <b>Returned by admin:</b> {report.admin_note}
                </div>
            )}
            <ReportPreview report={report} />
        </Layout>
    );
}
