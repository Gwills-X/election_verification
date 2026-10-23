import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import DonutChart from "../../Components/DonutChart";
import StatusBadge from "../../Components/StatusBadge";
import EmptyState from "../../Components/EmptyState";

export default function Dashboard({ elections, stats }) {
    return (
        <AppLayout title="Observer Dashboard">
            <p className="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-2 text-sm text-blue-800">
                Read-only access. Observers can view elections, results,
                validation statuses and statistics, but cannot modify any data.
            </p>
            <div className="grid gap-4 lg:grid-cols-2">
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-4 font-semibold text-slate-800">
                        Validation Status (all published elections)
                    </h3>
                    <DonutChart data={stats} />
                </div>
                <div className="rounded-lg border bg-white p-5 shadow-sm">
                    <h3 className="mb-3 font-semibold text-slate-800">
                        Available Elections
                    </h3>
                    {elections.length ? (
                        <ul className="divide-y text-sm">
                            {elections.map((e) => (
                                <li
                                    key={e.id}
                                    className="flex items-center justify-between py-2"
                                >
                                    <Link
                                        href={route(
                                            "observer.elections.show",
                                            e.id,
                                        )}
                                        className="font-medium text-blue-700 hover:underline"
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
                    ) : (
                        <EmptyState
                            title="No published elections"
                            message="Published elections will appear here when they are available."
                        />
                    )}
                </div>
            </div>
            <Link
                href={route("observer.results")}
                className="mt-6 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
            >
                Search all results
            </Link>
        </AppLayout>
    );
}
