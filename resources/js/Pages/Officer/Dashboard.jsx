import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import StatCard from "../../Components/StatCard";

export default function Dashboard({
    assignedElections,
    awaitingReview,
    flagged,
    reviewedByMe,
    reviewedTotal,
}) {
    return (
        <AppLayout title="Election Officer Dashboard">
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    label="Assigned Elections"
                    value={assignedElections.length}
                    tone="blue"
                />
                <StatCard
                    label="Awaiting Review"
                    value={awaitingReview}
                    tone="amber"
                />
                <StatCard label="Flagged Records" value={flagged} tone="red" />
                <StatCard
                    label="Reviewed by me"
                    value={reviewedByMe}
                    tone="green"
                />
            </div>
            <p className="mt-2 text-xs text-gray-400">
                {reviewedTotal} records reviewed in total across your assigned
                elections.
            </p>

            <h3 className="mt-8 mb-3 font-semibold text-slate-800">
                My Elections
            </h3>
            {assignedElections.length === 0 ? (
                <p className="rounded-lg border border-dashed bg-white p-10 text-center text-gray-400">
                    No elections assigned to you yet. Contact the system
                    administrator.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {assignedElections.map((e) => (
                        <div
                            key={e.id}
                            className="rounded-lg border bg-white p-5 shadow-sm"
                        >
                            <h4 className="font-semibold text-slate-800">
                                {e.name}
                            </h4>
                            <p className="mt-1 text-sm text-gray-500">
                                {e.election_type?.name} · {e.year} ·{" "}
                                {e.election_type?.scope === "NATIONAL"
                                    ? "National"
                                    : e.election_type?.scope_state?.name}
                            </p>
                            <p className="mt-1 text-sm text-gray-500">
                                {e.results_count} result records
                            </p>
                            <Link
                                href={route("officer.elections.show", e.id)}
                                className="mt-4 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
                            >
                                Open
                            </Link>
                        </div>
                    ))}
                </div>
            )}

            <Link
                href={route("officer.queue")}
                className="mt-6 inline-block rounded-md border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700"
            >
                Open review queue ({awaitingReview})
            </Link>
        </AppLayout>
    );
}
