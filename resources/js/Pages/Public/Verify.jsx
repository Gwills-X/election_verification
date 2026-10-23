import { Link, router } from "@inertiajs/react";
import PublicLayout from "../../Components/PublicLayout";
import StatusBadge from "../../Components/StatusBadge";
import ReviewBadge from "../../Components/ReviewBadge";
import EmptyState from "../../Components/EmptyState";
import Pagination from "../../Components/Pagination";

const sel = "rounded-md border-gray-300 text-sm shadow-sm";

export default function Verify({
    elections,
    election,
    states,
    lgas,
    wards,
    results,
    filters,
}) {
    const f = {
        election_id: election?.id ?? "",
        state: "",
        lga: "",
        ward: "",
        q: "",
        ...filters,
    };
    const go = (patch) =>
        router.get(
            route("verify.index"),
            { ...f, ...patch },
            { preserveState: true },
        );
    return (
        <PublicLayout>
            <h1 className="mb-1 text-2xl font-bold text-slate-800">
                Verify Election Results
            </h1>
            <p className="mb-6 text-sm text-gray-500">
                Select an election, then narrow down by location to find a
                polling unit.
            </p>

            <div className="mb-6 grid gap-3 rounded-lg border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
                <select
                    className={sel}
                    value={f.election_id}
                    onChange={(e) =>
                        go({
                            election_id: e.target.value,
                            state: "",
                            lga: "",
                            ward: "",
                        })
                    }
                >
                    {elections.map((e) => (
                        <option key={e.id} value={e.id}>
                            {e.name}
                        </option>
                    ))}
                </select>
                <select
                    className={sel}
                    value={f.state ?? ""}
                    onChange={(e) =>
                        go({ state: e.target.value, lga: "", ward: "" })
                    }
                >
                    <option value="">All states</option>
                    {states.map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
                <select
                    className={sel}
                    value={f.lga ?? ""}
                    onChange={(e) => go({ lga: e.target.value, ward: "" })}
                >
                    <option value="">All LGAs</option>
                    {lgas.map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
                <select
                    className={sel}
                    value={f.ward ?? ""}
                    onChange={(e) => go({ ward: e.target.value })}
                >
                    <option value="">All wards</option>
                    {wards.map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        go({ q: f.q });
                    }}
                    className="flex gap-2"
                >
                    <input
                        className={sel + " w-full"}
                        placeholder="PU code or name"
                        value={f.q ?? ""}
                        onChange={(e) => go({ q: e.target.value })}
                    />
                </form>
            </div>

            {results.data.length === 0 ? (
                <EmptyState
                    title="No results match your search"
                    message="Try a different location or polling-unit code."
                />
            ) : (
                <>
                    <div className="overflow-x-auto rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">PU Code</th>
                                    <th className="px-4 py-3">Polling Unit</th>
                                    <th className="px-4 py-3">LGA</th>
                                    <th className="px-4 py-3">Ward</th>
                                    <th className="px-4 py-3">Validation</th>
                                    <th className="px-4 py-3">Review</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {results.data.map((r) => (
                                    <tr key={r.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-mono text-xs">
                                            <Link
                                                href={route(
                                                    "verify.show",
                                                    r.id,
                                                )}
                                                className="text-blue-700 hover:underline"
                                            >
                                                {r.polling_unit?.code}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {r.polling_unit?.name ?? "—"}
                                        </td>
                                        <td className="px-4 py-3">
                                            {r.polling_unit?.ward
                                                ?.local_government?.name ?? "—"}
                                        </td>
                                        <td className="px-4 py-3">
                                            {r.polling_unit?.ward?.name ?? "—"}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge
                                                status={r.validation_status}
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            <ReviewBadge result={r} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={results.links} />
                </>
            )}
        </PublicLayout>
    );
}
