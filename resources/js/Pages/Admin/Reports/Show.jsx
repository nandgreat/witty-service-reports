import { Head, router, useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import ReportPreview from '../../../Components/ReportPreview';
import { Button, Card, Field, StatusBadge } from '../../../Components/ui';

export default function Show({ report }) {
    const base = `/admin/reports/${report.id}`;
    const { data, setData, post, processing, errors } = useForm({ decision: 'approve', admin_note: report.admin_note ?? '' });
    const decide = (decision) => {
        setData('decision', decision);
        router.post(`${base}/review`, { decision, admin_note: data.admin_note }, { preserveScroll: true });
    };

    return (
        <Layout
            title={<span className="flex flex-wrap items-center gap-3">{report.service_type} · {report.service_date_label} <StatusBadge status={report.status} /></span>}
            actions={<>
                <Button href="/admin/reports" variant="ghost">← All reports</Button>
                <Button as="a" href={`${base}/download`}>Download .docx</Button>
                <Button variant="danger" onClick={() => confirm('Delete this report permanently?') && router.delete(base)}>Delete</Button>
            </>}>
            <Head title="Report" />
            <p className="mb-3 text-sm text-slate-500">Prepared by <b>{report.prepared_by || 'not specified'}</b>
                {report.coordinator && report.coordinator !== report.prepared_by && <> · entered by {report.coordinator}</>}</p>
            <Card title="Review" className="mb-5">
                <Field label="Note to coordinator (required when returning)" error={errors.admin_note}>
                    <textarea rows={2} value={data.admin_note} onChange={(e) => setData('admin_note', e.target.value)} />
                </Field>
                <div className="mt-3 flex gap-2">
                    <Button disabled={processing || report.status === 'draft'} onClick={() => decide('approve')}>Approve</Button>
                    <Button variant="secondary" disabled={processing || report.status === 'draft'} onClick={() => decide('return')}>Return for changes</Button>
                    {report.status === 'draft' && <span className="self-center text-xs text-slate-500">Still a draft; the coordinator has not submitted it yet.</span>}
                </div>
            </Card>
            <ReportPreview report={report} />
        </Layout>
    );
}
