import { Link } from '@inertiajs/react';
const items = [['admin.dashboard', 'Overview'], ['admin.import', 'Import'], ['admin.results', 'Results'], ['admin.flagged', 'Flagged'], ['admin.audit', 'Audit logs']];
export default function AdminNav() {
    return (
        <nav className="mb-6 flex flex-wrap gap-2">
            {items.map(([r, l]) => (
                <Link key={r} href={route(r)} className={`rounded-md px-3 py-1.5 text-sm ${route().current(r) ? 'bg-slate-800 text-white' : 'border bg-white'}`}>{l}</Link>
            ))}
        </nav>
    );
}
