import { Link } from '@inertiajs/react';

export function Button({ as, href, variant = 'primary', className = '', children, ...rest }) {
    const styles = {
        primary: 'bg-brand-700 text-white hover:bg-brand-800',
        secondary: 'bg-white text-brand-700 border border-brand-200 hover:bg-brand-50',
        danger: 'bg-white text-red-600 border border-red-200 hover:bg-red-50',
        ghost: 'text-slate-600 hover:bg-slate-100',
    }[variant];
    const cls = `inline-flex items-center justify-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-semibold transition disabled:opacity-50 ${styles} ${className}`;
    if (as === 'a') return <a href={href} className={cls} {...rest}>{children}</a>;
    if (href) return <Link href={href} className={cls} {...rest}>{children}</Link>;
    return <button type="button" {...rest} className={cls}>{children}</button>;
}

export function Card({ title, hint, actions, children, className = '' }) {
    return (
        <section className={`rounded-xl border border-slate-200 bg-white shadow-sm ${className}`}>
            {(title || actions) && (
                <header className="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <div>
                        <h2 className="text-sm font-bold text-brand-700">{title}</h2>
                        {hint && <p className="mt-0.5 text-xs text-slate-500">{hint}</p>}
                    </div>
                    {actions}
                </header>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}

export function Field({ label, error, children, className = '' }) {
    return (
        <div className={className}>
            {label && <label>{label}</label>}
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

const STATUS = {
    draft: ['Draft', 'bg-slate-100 text-slate-700'],
    submitted: ['Awaiting review', 'bg-amber-100 text-amber-800'],
    approved: ['Approved', 'bg-emerald-100 text-emerald-800'],
    returned: ['Returned', 'bg-red-100 text-red-700'],
};

export function StatusBadge({ status }) {
    const [label, cls] = STATUS[status] ?? [status, 'bg-slate-100'];
    return <span className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${cls}`}>{label}</span>;
}

export function Table({ head, children, empty }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
                <thead>
                    <tr className="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        {head.map((h, i) => <th key={i} className="px-3 py-2 font-semibold">{h}</th>)}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">{children}</tbody>
            </table>
            {empty && <p className="py-8 text-center text-sm text-slate-500">{empty}</p>}
        </div>
    );
}

/**
 * Dropdown of members. `multiple` keeps a comma-separated string (so several people
 * can share one task); otherwise it is a plain single select.
 */
export function MemberSelect({ value, onChange, members, multiple = false, placeholder = 'Select…' }) {
    const names = multiple ? (value || '').split(',').map((n) => n.trim()).filter(Boolean) : [];
    const all = members.map((m) => m.name);

    if (!multiple) {
        return (
            <select value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                <option value="">{placeholder}</option>
                {value && !all.includes(value) && <option value={value}>{value}</option>}
                {members.map((m) => <option key={m.name} value={m.name}>{m.name}{m.role ? ` (${m.role})` : ''}</option>)}
            </select>
        );
    }

    const commit = (list) => onChange(list.join(', '));
    return (
        <div>
            {names.length > 0 && (
                <div className="mb-1.5 flex flex-wrap gap-1">
                    {names.map((n) => (
                        <span key={n} className="inline-flex items-center gap-1 rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800">
                            {n}
                            <button type="button" className="text-brand-700 hover:text-red-600" onClick={() => commit(names.filter((x) => x !== n))}>×</button>
                        </span>
                    ))}
                </div>
            )}
            <select value="" onChange={(e) => e.target.value && commit([...names, e.target.value])}>
                <option value="">{names.length ? '+ Add another…' : placeholder}</option>
                {members.filter((m) => !names.includes(m.name)).map((m) => (
                    <option key={m.name} value={m.name}>{m.name}{m.role ? ` (${m.role})` : ''}</option>
                ))}
            </select>
        </div>
    );
}
