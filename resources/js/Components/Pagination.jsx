import { Link } from '@inertiajs/react';
export default function Pagination({ links = [] }) {
    if (links.length <= 3) return null;
    return (
        <nav className="mt-4 flex flex-wrap gap-1" aria-label="Pagination">
            {links.map((l, i) => l.url ? (
                <Link key={i} href={l.url} preserveScroll
                    className={`rounded border px-3 py-1 text-sm ${l.active ? 'bg-slate-800 text-white' : 'bg-white hover:bg-gray-50'}`}
                    dangerouslySetInnerHTML={{ __html: l.label }} />
            ) : (
                <span key={i} className="rounded border px-3 py-1 text-sm text-gray-400" dangerouslySetInnerHTML={{ __html: l.label }} />
            ))}
        </nav>
    );
}
