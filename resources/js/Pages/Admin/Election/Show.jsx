import { Link, router } from "@inertiajs/react";
import AppLayout from "../../../Components/AppLayout";
import StatusBadge from "../../../Components/StatusBadge";
import DonutChart from "../../../Components/DonutChart";
import BarList from "../../../Components/BarList";
import ReviewBadge from "../../../Components/ReviewBadge";
import EmptyState from "../../../Components/EmptyState";
import Pagination from "../../../Components/Pagination";

export default function Show({
    election,
    statusCounts,
    candidateTotals,
    officers,
    imports,
    results,
}) {
    const archive = () => {
        if (confirm("Archive this election?"))
            router.post(route("admin.elections.archive", election.id));
    };
    return (
        <AppLayout title={election.name}>
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <StatusBadge status={election.status} />
                <span className="text-sm text-gray-500">
                    {election.election_type?.name} · {election.year} ·{" "}
                    {election.election_type?.scope === "NATIONAL"
                        ? "National"
                        : election.election_type?.scope_state?.name}
                </span>
                <span className="flex-1" />
                {election.is_editable !== false && (
                    <>
                        <Link
                            href={`${route("admin.import")}?election_id=${election.id}`}
                            className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
                        >
                            Import dataset
                        </Link>
                        <Link
                            href={route("admin.elections.edit", election.id)}
                            className="rounded-md border px-4 py-2 text-sm"
                        >
                            Edit
                        </Link>
                        {election.status !== "ARCHIVED" && (
                            <button
                                onClick={archive}
                                className="rounded-md border border-amber-300 px-4 py-2 text-sm text-amber-700"
                            >
                                Archive
                            </button>
                        )}
                    </>
                )}
            </div>
            {election.description && (
                <p className="mb-6 max-w-3xl text-sm text-gray-600">
                    {election.description}
                </p>
            )}

            <div className="grid gap-4 lg:grid-cols-2">
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-4 font-semibold text-slate-800">
                        Validation Status
                    </h3>
                    <DonutChart data={statusCounts} />
                </div>
                <BarList
                    data={candidateTotals}
                    title="Candidate / Party Totals"
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-3 font-semibold text-slate-800">
                        Assigned Officers
                    </h3>
                    {officers.length === 0 ? (
                        <EmptyState message="No officers assigned. Assign them from User management." />
                    ) : (
                        <ul className="divide-y text-sm">
                            {officers.map((o) => (
                                <li key={o.id} className="py-2">
                                    {o.name}{" "}
                                    <span className="text-xs text-gray-400">
                                        ({o.email})
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-3 font-semibold text-slate-800">
                        Dataset Imports
                    </h3>
                    {imports.length === 0 ? (
                        <EmptyState message="No datasets imported for this election." />
                    ) : (
                        <ul className="divide-y text-sm">
                            {imports.map((i) => (
                                <li key={i.id} className="py-2">
                                    <p className="font-medium">
                                        {i.filename}{" "}
                                        {i.dataset_type === "SYNTHETIC" && (
                                            <span className="rounded bg-amber-100 px-1.5 text-xs text-amber-800">
                                                test data
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {i.records_imported}/{i.records_found}{" "}
                                        imported ({i.valid} valid, {i.invalid}{" "}
                                        invalid, {i.flagged} flagged) · by{" "}
                                        {i.user?.name ?? "—"}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <h3 className="mt-6 mb-3 font-semibold text-slate-800">
                Recent Result Records
            </h3>
            {results.data.length === 0 ? (
                <EmptyState />
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
                                                    "admin.results.show",
                                                    r.id,
                                                )}
                                                className="text-blue-700 hover:underline"
                                            >
                                                {r.polling_unit?.code}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {r.polling_unit?.name}
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
        </AppLayout>
    );
}
