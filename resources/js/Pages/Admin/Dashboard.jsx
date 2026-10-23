import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import StatCard from "../../Components/StatCard";
import DonutChart from "../../Components/DonutChart";
import BarList from "../../Components/BarList";
import StatusBadge from "../../Components/StatusBadge";
import EmptyState from "../../Components/EmptyState";

export default function Dashboard({
    stats,
    elections,
    recentImports,
    recentReviews,
    recentActivity,
    candidateTotals,
}) {
    return (
        <AppLayout title="Administrator Dashboard">
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Elections"
                    value={stats.elections}
                    tone="blue"
                />
                <StatCard label="Polling Units" value={stats.polling_units} />
                <StatCard label="Result Records" value={stats.records} />
                <StatCard
                    label="Records Reviewed"
                    value={stats.reviewed}
                    tone="green"
                />
                <StatCard label="Valid" value={stats.valid} tone="green" />
                <StatCard label="Invalid" value={stats.invalid} tone="red" />
                <StatCard label="Flagged" value={stats.flagged} tone="amber" />
                <StatCard label="Pending" value={stats.pending} />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-4 font-semibold text-slate-800">
                        Validation Status
                    </h3>
                    <DonutChart
                        data={{
                            valid: stats.valid,
                            invalid: stats.invalid,
                            flagged: stats.flagged,
                            pending: stats.pending,
                        }}
                    />
                </div>
                <BarList
                    data={candidateTotals}
                    title="Candidate / Party Totals (all elections)"
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold text-slate-800">
                            Elections
                        </h3>
                        <Link
                            href={route("admin.elections.index")}
                            className="text-sm text-blue-700 hover:underline"
                        >
                            Manage →
                        </Link>
                    </div>
                    {elections.length === 0 ? (
                        <EmptyState />
                    ) : (
                        <ul className="divide-y text-sm">
                            {elections.map((e) => (
                                <li
                                    key={e.id}
                                    className="flex items-center justify-between gap-2 py-2"
                                >
                                    <Link
                                        href={route(
                                            "admin.elections.show",
                                            e.id,
                                        )}
                                        className="font-medium text-slate-700 hover:underline"
                                    >
                                        {e.name}
                                    </Link>
                                    <span className="flex items-center gap-2">
                                        <span className="text-xs text-gray-400">
                                            {e.results_count} records
                                        </span>
                                        <StatusBadge status={e.status} />
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-3 font-semibold text-slate-800">
                        Recent Dataset Uploads
                    </h3>
                    {recentImports.length === 0 ? (
                        <EmptyState message="No datasets imported yet." />
                    ) : (
                        <ul className="divide-y text-sm">
                            {recentImports.map((i) => (
                                <li key={i.id} className="py-2">
                                    <p className="font-medium">
                                        {i.election?.name}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {i.filename} · {i.records_imported}/
                                        {i.records_found} imported · by{" "}
                                        {i.user?.name ?? "—"} ·{" "}
                                        {new Date(
                                            i.created_at,
                                        ).toLocaleString()}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-3 font-semibold text-slate-800">
                        Recent Reviews
                    </h3>
                    {recentReviews.length === 0 ? (
                        <EmptyState message="No records reviewed yet." />
                    ) : (
                        <ul className="divide-y text-sm">
                            {recentReviews.map((r) => (
                                <li key={r.id} className="py-2">
                                    <Link
                                        href={route("admin.results.show", r.id)}
                                        className="font-medium text-blue-700 hover:underline"
                                    >
                                        {r.polling_unit?.code} —{" "}
                                        {r.polling_unit?.name}
                                    </Link>
                                    <p className="text-xs text-gray-500">
                                        Reviewed by {r.reviewer?.name ?? "—"} ·{" "}
                                        {r.reviewed_at
                                            ? new Date(
                                                  r.reviewed_at,
                                              ).toLocaleString()
                                            : ""}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold text-slate-800">
                            Recent Activity
                        </h3>
                        <Link
                            href={route("admin.audit")}
                            className="text-sm text-blue-700 hover:underline"
                        >
                            Audit log →
                        </Link>
                    </div>
                    {recentActivity.length ? (
                        <ul className="divide-y text-sm">
                            {recentActivity.map((a) => (
                                <li key={a.id} className="py-2">
                                    <p className="text-xs font-semibold text-slate-600">
                                        {a.action}{" "}
                                        <span className="font-normal text-gray-400">
                                            · {a.user?.name ?? "System"}
                                        </span>
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {a.description}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState message="No audit activity is available." />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
