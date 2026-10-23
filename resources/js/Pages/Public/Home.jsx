import { Link } from "@inertiajs/react";
import PublicLayout from "../../Components/PublicLayout";
import StatusBadge from "../../Components/StatusBadge";

export default function Home({ elections, stats }) {
    return (
        <PublicLayout>
            <section className="rounded-2xl bg-slate-900 px-8 py-14 text-center text-white">
                <h1 className="text-3xl font-bold sm:text-4xl">
                    Election Result Verification
                </h1>
                <p className="mx-auto mt-3 max-w-2xl text-slate-300">
                    Search polling-unit results, view validation statuses and
                    traceable review information for published elections.
                </p>
                <Link
                    href={route("verify.index")}
                    className="mt-6 inline-block rounded-md bg-white px-6 py-3 font-semibold text-slate-900 hover:bg-slate-100"
                >
                    Verify a Result
                </Link>
            </section>

            <section className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {[
                    ["Published elections", elections.length],
                    ["Result records", stats.total],
                    ["Valid records", stats.valid],
                    ["Flagged for review", stats.flagged],
                ].map(([l, v]) => (
                    <div
                        key={l}
                        className="rounded-lg border bg-white p-4 text-center shadow-sm"
                    >
                        <p className="text-2xl font-bold text-slate-800">
                            {v ?? 0}
                        </p>
                        <p className="mt-1 text-xs uppercase tracking-wide text-gray-500">
                            {l}
                        </p>
                    </div>
                ))}
            </section>

            <h2 className="mt-10 mb-4 text-lg font-semibold text-slate-800">
                Available elections
            </h2>
            {elections.length === 0 ? (
                <p className="rounded-lg border border-dashed bg-white p-10 text-center text-gray-400">
                    No elections have been published yet.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {elections.map((e) => (
                        <div
                            key={e.id}
                            className="rounded-lg border bg-white p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <h3 className="font-semibold text-slate-800">
                                    {e.name}
                                </h3>
                                <StatusBadge status={e.status} />
                            </div>
                            <p className="mt-2 text-sm text-gray-500">
                                {e.election_type?.name} · {e.year} ·{" "}
                                {e.election_type?.scope === "NATIONAL"
                                    ? "National"
                                    : e.election_type?.scope_state?.name}
                            </p>
                            <p className="mt-1 text-sm text-gray-500">
                                {e.results_count} polling-unit records
                            </p>
                            <Link
                                href={route("verify.index", {
                                    election_id: e.id,
                                })}
                                className="mt-4 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                            >
                                Search results
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </PublicLayout>
    );
}
