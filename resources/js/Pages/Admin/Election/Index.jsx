import { Link, router } from "@inertiajs/react";
import AppLayout from "../../../Components/AppLayout";
import StatusBadge from "../../../Components/StatusBadge";
import Pagination from "../../../Components/Pagination";
import EmptyState from "../../../Components/EmptyState";

export default function Index({ elections, filters, statuses }) {
    const archive = (e) => {
        if (
            confirm(
                "Archive this election? It will be kept for historical reference only.",
            )
        )
            router.post(route("admin.elections.archive", e.id));
    };
    const destroy = (e) => {
        if (confirm("Delete this election? This cannot be undone."))
            router.delete(route("admin.elections.destroy", e.id));
    };
    return (
        <AppLayout title="Elections">
            <div className="mb-4 flex items-center justify-between">
                <select
                    value={filters.status ?? ""}
                    onChange={(ev) =>
                        router.get(
                            route("admin.elections.index"),
                            { status: ev.target.value },
                            { preserveState: true },
                        )
                    }
                    className="rounded-md border-gray-300 text-sm shadow-sm"
                >
                    <option value="">All statuses</option>
                    {statuses.map((s) => (
                        <option key={s}>{s}</option>
                    ))}
                </select>
                <Link
                    href={route("admin.elections.create")}
                    className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
                >
                    Create Election
                </Link>
            </div>
            {elections.data.length === 0 ? (
                <EmptyState
                    title="No elections yet"
                    message="Create your first election to start importing result datasets."
                />
            ) : (
                <>
                    <div className="overflow-x-auto rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Type</th>
                                    <th className="px-4 py-3">Year</th>
                                    <th className="px-4 py-3">Scope</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Records</th>
                                    <th className="px-4 py-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {elections.data.map((e) => (
                                    <tr key={e.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium">
                                            <Link
                                                href={route(
                                                    "admin.elections.show",
                                                    e.id,
                                                )}
                                                className="text-blue-700 hover:underline"
                                            >
                                                {e.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            {e.election_type?.name ?? "—"}
                                        </td>
                                        <td className="px-4 py-3">{e.year}</td>
                                        <td className="px-4 py-3">
                                            {e.election_type?.scope ===
                                            "NATIONAL"
                                                ? "National"
                                                : (e.election_type?.scope_state
                                                      ?.name ?? "—")}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={e.status} />
                                        </td>
                                        <td className="px-4 py-3">
                                            {e.results_count}
                                        </td>
                                        <td className="px-4 py-3 text-right text-xs">
                                            <Link
                                                href={route(
                                                    "admin.elections.edit",
                                                    e.id,
                                                )}
                                                className="text-blue-700 hover:underline"
                                            >
                                                Edit
                                            </Link>
                                            {e.status !== "ARCHIVED" && (
                                                <button
                                                    onClick={() => archive(e)}
                                                    className="ml-3 text-amber-700 hover:underline"
                                                >
                                                    Archive
                                                </button>
                                            )}
                                            {e.results_count === 0 && (
                                                <button
                                                    onClick={() => destroy(e)}
                                                    className="ml-3 text-red-700 hover:underline"
                                                >
                                                    Delete
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={elections.links} />
                </>
            )}
        </AppLayout>
    );
}
