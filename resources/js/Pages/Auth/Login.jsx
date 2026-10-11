import { Head, useForm } from '@inertiajs/react';
import { Button, Field } from '../../Components/ui';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false });

    return (
        <div className="flex min-h-screen items-center justify-center bg-brand-50 px-4">
            <Head title="Sign in" />
            <form onSubmit={(e) => { e.preventDefault(); post('/login'); }}
                className="w-full max-w-sm rounded-2xl border border-brand-100 bg-white p-8 shadow-sm">
                <img src="/img/logo.png" alt="Witty Inventions" className="mx-auto h-20 w-20" />
                <h1 className="mt-3 text-center text-lg font-bold text-brand-700">Service Report Generator</h1>
                <p className="mb-6 text-center text-xs text-slate-500">Witty Inventions · Church of Zion Assembly</p>
                <div className="space-y-4">
                    <Field label="Email" error={errors.email}>
                        <input type="email" autoFocus value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </Field>
                    <Field label="Password" error={errors.password}>
                        <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    </Field>
                    <label className="flex items-center gap-2 text-sm font-normal normal-case tracking-normal text-slate-600">
                        <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} /> Remember me
                    </label>
                    <Button type="submit" className="w-full" disabled={processing} onClick={undefined}>Sign in</Button>
                </div>
            </form>
        </div>
    );
}
