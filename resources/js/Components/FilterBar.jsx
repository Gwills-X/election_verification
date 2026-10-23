import { router } from '@inertiajs/react';
import { useState } from 'react';
const field = 'rounded-md border-gray-300 text-sm shadow-sm';
export default function FilterBar({ routeName, filters, lgas, wards }) {
    const [v, setV] = useState({ q: '', lga: '', ward: '', status: '', ...filters });
    const set = (k) => (e) => setV({ ...v, [k]: e.target.value });
    const submit = (e) => { e.preventDefault(); router.get(route(routeName), v, { preserveState: true }); };
    const reset = () => { setV({ q: '', lga: '', ward: '', status: '' }); router.get(route(routeName)); };
    return (
        <form onSubmit={submit} className="mb-4 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
            <input className={field} placeholder="Polling unit code or name" value={v.q} onChange={set('q')} aria-label="Search" />
            <select className={field} value={v.lga} onChange={set('lga')} aria-label="LGA"><option value="">All LGAs</option>{lgas.map((x) => <option key={x}>{x}</option>)}</select>
            <select className={field} value={v.ward} onChange={set('ward')} aria-label="Ward"><option value="">All wards</option>{wards.map((x) => <option key={x}>{x}</option>)}</select>
            <select className={field} value={v.status} onChange={set('status')} aria-label="Status">
                <option value="">All statuses</option><option value="VALID">Valid</option><option value="INVALID">Invalid</option>
                <option value="FLAGGED_FOR_REVIEW">Flagged for review</option><option value="PENDING">Pending</option>
            </select>
            <div className="flex gap-2">
                <button className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white">Apply</button>
                <button type="button" onClick={reset} className="rounded-md border px-4 py-2 text-sm">Reset</button>
            </div>
        </form>
    );
}
