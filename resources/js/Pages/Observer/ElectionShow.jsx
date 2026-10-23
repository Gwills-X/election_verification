import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import DonutChart from "../../Components/DonutChart";
import BarList from "../../Components/BarList";
import StatCard from "../../Components/StatCard";

export default function ElectionShow({
    election,
    statusCounts,
    candidateTotals,
}) {
    return (
        <AppLayout title={election.name}>
            <p className="mb-6 text-sm text-gray-500">
                {election.election_type?.name} · {election.year} ·{" "}
                {election.election_type?.scope === "NATIONAL"
                    ? "National"
                    : election.election_type?.scope_state?.name}
            </p>
            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Total records" value={statusCounts.total} />
                <StatCard
                    label="Valid"
                    value={statusCounts.valid}
                    tone="green"
                />
                <StatCard
                    label="Invalid"
                    value={statusCounts.invalid}
                    tone="red"
                />
                <StatCard
                    label="Flagged"
                    value={statusCounts.flagged}
                    tone="amber"
                />
            </div>
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
            <Link
                href={route("observer.results", { election_id: election.id })}
                className="mt-6 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
            >
                Browse results
            </Link>
        </AppLayout>
    );
}
