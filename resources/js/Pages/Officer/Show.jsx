import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import ReviewBox from "../../Components/ReviewBox";
import StatusBadge from "../../Components/StatusBadge";

export default function Show({ result, backUrl }) {
    const candidateEntries = (result.candidate_votes ?? []).map((vote) => [
        vote.party?.code ?? vote.party?.name ?? "Candidate",
        vote.votes,
    ]);
    const ward = result.polling_unit?.ward;
    const localGovernment = ward?.local_government;
    const state = localGovernment?.state;

    return (
        <AppLayout
            title={`Review ${result.polling_unit?.code ?? "#" + result.id}`}
        >
            <Link
                href={backUrl ?? route("officer.queue")}
                className="text-sm text-blue-700 hover:underline"
            >
                ← Back to review queue
            </Link>

            <div className="mt-4 grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Election
                                </p>
                                <h3 className="mt-1 text-xl font-semibold text-slate-800">
                                    {result.election?.name ?? "—"}
                                </h3>
                            </div>
                            <div>
                                <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Current result status
                                </p>
                                <StatusBadge
                                    status={result.validation_status}
                                />
                            </div>
                        </div>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div className="rounded-md border border-slate-200 bg-slate-50 p-3">
                                <p className="text-xs uppercase tracking-wide text-slate-500">
                                    Polling unit
                                </p>
                                <p className="mt-1 text-lg font-semibold text-slate-800">
                                    {result.polling_unit?.code ?? "—"}
                                </p>
                                <p className="text-sm text-slate-600">
                                    {result.polling_unit?.name ?? "—"}
                                </p>
                            </div>
                            <div className="rounded-md border border-slate-200 bg-slate-50 p-3">
                                <p className="text-xs uppercase tracking-wide text-slate-500">
                                    Location
                                </p>
                                <p className="mt-1 text-sm text-slate-700">
                                    LGA: {localGovernment?.name ?? "—"}
                                </p>
                                <p className="text-sm text-slate-700">
                                    Ward: {ward?.name ?? "—"}
                                </p>
                                <p className="text-sm text-slate-700">
                                    State: {state?.name ?? "—"}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <h3 className="font-semibold text-slate-800">
                            Result details
                        </h3>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            {[
                                ["Registered voters", result.registered_voters],
                                ["Accredited voters", result.accredited_voters],
                                ["Rejected votes", result.rejected_votes],
                                ["Total votes", result.total_votes],
                            ].map(([label, value]) => (
                                <div
                                    key={label}
                                    className="rounded-md border border-slate-200 bg-slate-50 p-3"
                                >
                                    <p className="text-[10px] uppercase tracking-wide text-slate-500">
                                        {label}
                                    </p>
                                    <p className="mt-1 text-lg font-semibold text-slate-800">
                                        {value ?? "—"}
                                    </p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-5">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Candidate totals
                            </p>
                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {candidateEntries.length > 0 ? (
                                    candidateEntries.map(
                                        ([candidate, votes]) => (
                                            <div
                                                key={candidate}
                                                className="rounded-md border border-slate-200 p-3"
                                            >
                                                <p className="text-xs uppercase tracking-wide text-slate-500">
                                                    {candidate}
                                                </p>
                                                <p className="mt-1 text-lg font-semibold text-slate-800">
                                                    {votes ?? 0}
                                                </p>
                                            </div>
                                        ),
                                    )
                                ) : (
                                    <p className="text-sm text-slate-500">
                                        No candidate totals available for this
                                        record.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border bg-white p-5 shadow-sm">
                        <h3 className="font-semibold text-slate-800">
                            Validation findings
                        </h3>
                        {result.validation_results?.length ? (
                            <ul className="mt-3 space-y-2 text-sm text-slate-700">
                                {result.validation_results.map((item) => (
                                    <li
                                        key={item.id}
                                        className="rounded-md border border-slate-200 p-3"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="font-medium text-slate-800">
                                                {item.validation_rule}
                                            </span>
                                            <StatusBadge status={item.status} />
                                        </div>
                                        <p className="mt-1 whitespace-pre-line text-slate-600">
                                            {item.validation_message}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-3 text-sm text-slate-500">
                                No validation findings recorded.
                            </p>
                        )}
                    </div>
                </div>

                <div>
                    <ReviewBox
                        result={result}
                        submitRoute="officer.results.review"
                    />
                </div>
            </div>
        </AppLayout>
    );
}
