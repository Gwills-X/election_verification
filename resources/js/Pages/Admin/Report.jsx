import { Link, router, usePage } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import StatCard from "../../Components/StatCard";
import EmptyState from "../../Components/EmptyState";

const DESCRIPTIONS = {
    election_summary:
        "Overview of elections with record, validation and review counts.",
    validation_summary: "Validation status counts and per-rule breakdown.",
    invalid_records: "Records that failed an objective validation rule.",
    flagged_records: "Records flagged for human review (e.g. duplicates).",
    reviewed_records: "Records marked as reviewed, with reviewer and notes.",
};

export default function Reports({ reports, elections, election, stats }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === "ADMIN";
    const indexRoute = isAdmin
        ? "admin.reports.index"
        : "officer.reports.index";
    const exportRoute = isAdmin
        ? "admin.reports.export"
        : "officer.reports.export";
    return (
        <AppLayout title="Reports">
            <div className="mb-6 flex items-center gap-3 rounded-lg border bg-white p-4 shadow-sm">
                <label className="text-sm font-medium text-gray-700">
                    Election:
                </label>
                <select
                    className="rounded-md border-gray-300 text-sm shadow-sm"
                    value={election?.id ?? ""}
                    onChange={(e) =>
                        router.get(
                            route(indexRoute),
                            { election_id: e.target.value },
                            { preserveState: true },
                        )
                    }
                >
                    <option value="" disabled={!isAdmin}>
                        {isAdmin ? "All elections" : "Select an election"}
                    </option>
                    {elections.map((e) => (
                        <option key={e.id} value={e.id}>
                            {e.name}
                        </option>
                    ))}
                </select>
            </div>

            {election && (
                <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <StatCard label="Records" value={stats.election_summary} />
                    <StatCard
                        label="Validation runs"
                        value={stats.validation_summary}
                        tone="blue"
                    />
                    <StatCard
                        label="Invalid"
                        value={stats.invalid_records}
                        tone="red"
                    />
                    <StatCard
                        label="Flagged"
                        value={stats.flagged_records}
                        tone="amber"
                    />
                    <StatCard
                        label="Reviewed"
                        value={stats.reviewed_records}
                        tone="green"
                    />
                </div>
            )}

            {!isAdmin && elections.length === 0 ? (
                <EmptyState
                    title="No elections assigned"
                    message="Reports and exports are available after an election is assigned to you."
                />
            ) : (
                <div
                    id="downloads"
                    className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    {Object.entries(reports).map(([key, name]) => (
                        <div
                            key={key}
                            className="flex flex-col rounded-lg border bg-white p-5 shadow-sm"
                        >
                            <h3 className="font-semibold text-slate-800">
                                {name}
                            </h3>
                            <p className="mt-1 flex-1 text-sm text-gray-500">
                                {DESCRIPTIONS[key]}
                            </p>
                            <a
                                href={route(exportRoute, {
                                    report: key,
                                    election_id: election?.id ?? "",
                                })}
                                className="mt-4 w-fit rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                            >
                                Download CSV
                            </a>
                        </div>
                    ))}
                </div>
            )}
            <p className="mt-4 text-xs text-gray-400">
                CSV files open directly in Excel. Reports use neutral validation
                terminology only.
            </p>
        </AppLayout>
    );
}
