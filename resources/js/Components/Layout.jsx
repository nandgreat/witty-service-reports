import { Link, router, usePage } from '@inertiajs/react';

const NAV = {
    admin: [
        ['Dashboard', '/admin'],
        ['Reports', '/admin/reports'],
        ['Service types', '/admin/catalog/service-types'],
        ['Service tasks', '/admin/catalog/tasks'],
        ['Platforms', '/admin/catalog/platforms'],
        ['Church branches', '/admin/catalog/church-branches'],
        ['Workers', '/admin/catalog/workers'],
        ['Users', '/admin/users'],
    ],
    coordinator: [
        ['My reports', '/coordinator/reports'],
        ['New report', '/coordinator/reports/create'],
    ],
};

export default function Layout({ children, title, actions }) {
    const page = usePage();
    const { auth, flash } = page.props;
    const url = page.url;
    const nav = NAV[auth.role] ?? [];
    const active = (href) => (href === '/admin' ? url === '/admin' : url.startsWith(href) && !(href === '/coordinator/reports' && url.startsWith('/coordinator/reports/create')));

    return (
        <div className="min-h-screen md:flex">
            <aside className="bg-brand-700 text-white md:fixed md:inset-y-0 md:w-60">
                <div className="flex items-center gap-3 px-5 py-4">
                    <img src="/img/logo.png" alt="" className="h-10 w-10 rounded-full bg-white p-0.5" />
                    <div className="leading-tight">
                        <div className="text-sm font-bold">Witty Inventions</div>
                        <div className="text-xs text-brand-200">{auth.role === 'admin' ? 'Admin' : 'Service coordinator'}</div>
                    </div>
                </div>
                <nav className="flex gap-1 overflow-x-auto px-3 pb-3 md:block md:space-y-1">
                    {nav.map(([label, href]) => (
                        <Link key={href} href={href}
                            className={`block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium ${active(href) ? 'bg-white/15' : 'hover:bg-white/10'}`}>
                            {label}
                        </Link>
                    ))}
                </nav>
                <div className="hidden border-t border-white/10 px-5 py-4 text-xs md:absolute md:inset-x-0 md:bottom-0 md:block">
                    <div className="truncate font-semibold">{auth.name}</div>
                    <div className="truncate text-brand-200">{auth.email}</div>
                    <button onClick={() => router.post('/logout')} className="mt-2 text-brand-100 underline">Sign out</button>
                </div>
            </aside>

            <main className="min-w-0 flex-1 md:ml-60">
                <div className="mx-auto max-w-6xl px-4 py-6 md:px-8">
                    <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <h1 className="text-xl font-bold text-slate-900">{title}</h1>
                        <div className="flex flex-wrap gap-2">{actions}</div>
                    </div>
                    {flash?.success && (
                        <div className="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-800">{flash.success}</div>
                    )}
                    {children}
                    <button onClick={() => router.post('/logout')} className="mt-8 text-xs text-slate-500 underline md:hidden">Sign out</button>
                </div>
            </main>
        </div>
    );
}
