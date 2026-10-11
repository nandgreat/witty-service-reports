import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';
import { Button, Card, Field } from '../../Components/ui';

function UserForm({ user, onDone }) {
    const form = useForm({
        name: user?.name ?? '', email: user?.email ?? '', password: '',
        role: user?.role ?? 'coordinator', is_active: user?.is_active ?? true,
    });
    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } };
        user ? form.put(`/admin/users/${user.id}`, opts) : form.post('/admin/users', opts);
    };
    return (
        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-2">
            <Field label="Name" error={form.errors.name}><input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /></Field>
            <Field label="Email" error={form.errors.email}><input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} /></Field>
            <Field label={user ? 'New password (leave blank to keep)' : 'Password'} error={form.errors.password}>
                <input type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
            </Field>
            <Field label="Role" error={form.errors.role}>
                <select value={form.data.role} onChange={(e) => form.setData('role', e.target.value)}>
                    <option value="coordinator">Service coordinator</option>
                    <option value="admin">Admin</option>
                </select>
            </Field>
            <label className="flex items-center gap-2 text-sm font-normal normal-case tracking-normal text-slate-700 sm:col-span-2">
                <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} /> Can sign in
            </label>
            <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={form.processing}>{user ? 'Save changes' : 'Create user'}</Button>
                <Button variant="ghost" onClick={onDone}>Cancel</Button>
            </div>
        </form>
    );
}

function PasswordForm({ user, onDone }) {
    const form = useForm({ password: '', password_confirmation: '' });
    const submit = (e) => {
        e.preventDefault();
        form.put(`/admin/users/${user.id}/password`, { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } });
    };
    return (
        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-2">
            <p className="text-sm text-slate-600 sm:col-span-2">
                Set a new password for <b>{user.name}</b>. They will be signed out of all devices and must use the new password.
            </p>
            <Field label="New password (min. 8 characters)" error={form.errors.password}>
                <input type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
            </Field>
            <Field label="Confirm new password">
                <input type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
            </Field>
            <div className="flex gap-2 sm:col-span-2">
                <Button type="submit" disabled={form.processing}>Change password</Button>
                <Button variant="ghost" onClick={onDone}>Cancel</Button>
            </div>
        </form>
    );
}

export default function Users({ users }) {
    const [resetting, setResetting] = useState(null);
    const [editing, setEditing] = useState(null);
    const [adding, setAdding] = useState(false);
    const me = usePage().props.auth.id;

    return (
        <Layout title="Users" actions={<Button onClick={() => { setAdding(!adding); setEditing(null); }}>{adding ? 'Close' : '+ Add user'}</Button>}>
            <Head title="Users" />
            {adding && <Card title="New user" className="mb-4"><UserForm onDone={() => setAdding(false)} /></Card>}
            <div className="space-y-2">
                {users.map((u) => (
                    <Card key={u.id} className={u.is_active ? '' : 'opacity-60'}>
                        {resetting === u.id ? <PasswordForm user={u} onDone={() => setResetting(null)} /> : editing === u.id ? <UserForm user={u} onDone={() => setEditing(null)} /> : (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div className="font-semibold">{u.name}
                                        <span className={`ml-2 rounded-full px-2 py-0.5 text-xs ${u.role === 'admin' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600'}`}>
                                            {u.role === 'admin' ? 'Admin' : 'Coordinator'}
                                        </span>
                                        {!u.is_active && <span className="ml-2 text-xs font-normal text-slate-500">(disabled)</span>}
                                    </div>
                                    <div className="text-xs text-slate-500">{u.email}</div>
                                </div>
                                <div className="flex gap-3 text-sm">
                                    <button className="font-semibold text-brand-700" onClick={() => { setEditing(u.id); setAdding(false); }}>Edit</button>
                                    <button className="font-semibold text-brand-700" onClick={() => { setResetting(u.id); setAdding(false); }}>Change password</button>
                                    {u.id !== me && (
                                        <button className={u.is_active ? 'text-amber-700' : 'font-semibold text-emerald-700'}
                                            onClick={() => router.patch(`/admin/users/${u.id}/toggle`, {}, { preserveScroll: true })}>
                                            {u.is_active ? 'Disable' : 'Enable'}
                                        </button>
                                    )}
                                    {u.id !== me && <button className="text-red-600" onClick={() => confirm(`Delete ${u.name} and all their reports?`) && router.delete(`/admin/users/${u.id}`, { preserveScroll: true })}>Delete</button>}
                                </div>
                            </div>
                        )}
                    </Card>
                ))}
            </div>
        </Layout>
    );
}
