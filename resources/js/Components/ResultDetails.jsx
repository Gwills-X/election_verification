import StatusBadge from "./StatusBadge";

export default function ResultDetails({
    result,
    includeReview = false,
    showReviewerName = false,
}) {
    const pollingUnit = result.polling_unit ?? {};
    const ward = pollingUnit.ward ?? {};
    const localGovernment = ward.local_government ?? {};
    const election = result.election ?? {};
    const candidates = (result.candidate_votes ?? []).map((vote) => [
        vote.party?.code ?? vote.party?.name ?? "Candidate",
        vote.votes,
    ]);
    const findings = result.validation_results ?? [];
    const reviewed = Boolean(result.reviewed_at);

    return (
        <div className="space-y-4">
            <section className="rounded-lg border bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 className="text-base font-semibold text-slate-800">
                            Election
                        </h2>
                        <p className="mt-1 text-lg font-semibold text-slate-800">
                            {election.name ?? "Election details unavailable"}
                        </p>
                        <p className="mt-1 text-sm text-slate-500">
                            {[
                                election.election_type?.name,
                                election.year,
                                election.election_type?.scope === "NATIONAL"
                                    ? "National"
                                    : election.election_type?.scope_state?.name,
                            ]
                                .filter(Boolean)
                                .join(" · ") || "—"}
                        </p>
                    </div>
                    <div>
                        <p className="mb-1 text-xs font-semibold uppercase text-slate-500">
                            Current result status
                        </p>
                        <StatusBadge status={result.validation_status} />
                    </div>
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div className="rounded-md border bg-slate-50 p-3">
                        <p className="text-xs font-semibold uppercase text-slate-500">
                            Polling unit
                        </p>
                        <p className="mt-1 font-semibold text-slate-800">
                            {pollingUnit.code ?? "—"}
                        </p>
                        <p className="text-sm text-slate-600">
                            {pollingUnit.name ?? "—"}
                        </p>
                    </div>
                    <div className="rounded-md border bg-slate-50 p-3">
                        <p className="text-xs font-semibold uppercase text-slate-500">
                            Location
                        </p>
                        <p className="mt-1 text-sm text-slate-700">
                            LGA: {localGovernment.name ?? "—"}
                        </p>
                        <p className="text-sm text-slate-700">
                            Ward: {ward.name ?? "—"}
                        </p>
                    </div>
                </div>
            </section>

            <section className="rounded-lg border bg-white p-5 shadow-sm">
                <h2 className="font-semibold text-slate-800">
                    Voter information
                </h2>
                <dl className="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    {[
                        ["Registered voters", result.registered_voters],
                        ["Accredited voters", result.accredited_voters],
                        ["Rejected votes", result.rejected_votes],
                        ["Total votes", result.total_votes],
                    ].map(([label, value]) => (
                        <div
                            key={label}
                            className="rounded-md border bg-slate-50 p-3"
                        >
                            <dt className="text-xs font-medium text-slate-500">
                                {label}
                            </dt>
                            <dd className="mt-1 text-lg font-semibold text-slate-800">
                                {value ?? "—"}
                            </dd>
                        </div>
                    ))}
                </dl>
            </section>

            <section className="rounded-lg border bg-white p-5 shadow-sm">
                <h2 className="font-semibold text-slate-800">
                    Candidate results
                </h2>
                {candidates.length ? (
                    <dl className="mt-3 grid gap-2 sm:grid-cols-2">
                        {candidates.map(([candidate, votes]) => (
                            <div
                                key={candidate}
                                className="flex items-center justify-between gap-3 rounded-md border px-3 py-2"
                            >
                                <dt className="text-sm font-medium text-slate-700">
                                    {candidate}
                                </dt>
                                <dd className="text-sm font-semibold text-slate-900">
                                    {votes ?? "—"}
                                </dd>
                            </div>
                        ))}
                    </dl>
                ) : (
                    <p className="mt-2 text-sm text-slate-500">
                        No candidate totals are available for this record.
                    </p>
                )}
            </section>

            <section className="rounded-lg border bg-white p-5 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="font-semibold text-slate-800">
                        Validation findings
                    </h2>
                    <StatusBadge status={result.validation_status} />
                </div>
                {findings.length ? (
                    <ul className="mt-3 space-y-2">
                        {findings.map((finding) => (
                            <li
                                key={finding.id}
                                className="rounded-md border bg-slate-50 p-3"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="font-mono text-xs font-semibold text-slate-700">
                                        {finding.validation_rule}
                                    </span>
                                    <StatusBadge status={finding.status} />
                                </div>
                                <p className="mt-1 whitespace-pre-line text-sm text-slate-600">
                                    {finding.validation_message}
                                </p>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-3 text-sm text-slate-500">
                        No automated validation findings were recorded for this
                        result.
                    </p>
                )}
                <p className="mt-3 text-xs text-slate-500">
                    Validation describes data consistency. It does not establish
                    whether an election event was accurate or fair.
                </p>
            </section>

            {includeReview && (
                <section className="rounded-lg border bg-white p-5 shadow-sm">
                    <h2 className="font-semibold text-slate-800">Review</h2>
                    <dl className="mt-3 space-y-2 text-sm">
                        <div>
                            <dt className="font-medium text-slate-700">
                                Review status
                            </dt>
                            <dd className="text-slate-600">
                                {reviewed ? "Reviewed" : "Not reviewed"}
                            </dd>
                        </div>
                        {reviewed && (
                            <>
                                <div>
                                    <dt className="font-medium text-slate-700">
                                        Reviewed by
                                    </dt>
                                    <dd className="text-slate-600">
                                        {showReviewerName
                                            ? (result.reviewer?.name ??
                                              "Unknown")
                                            : "Authorised officer"}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-medium text-slate-700">
                                        Reviewed at
                                    </dt>
                                    <dd className="text-slate-600">
                                        {new Date(
                                            result.reviewed_at,
                                        ).toLocaleString()}
                                    </dd>
                                </div>
                            </>
                        )}
                        <div>
                            <dt className="font-medium text-slate-700">
                                Review decision
                            </dt>
                            <dd className="text-slate-600">
                                {reviewed
                                    ? (result.review_decision ?? "—")
                                    : "Not yet decided"}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium text-slate-700">
                                Review note
                            </dt>
                            <dd className="whitespace-pre-line text-slate-600">
                                {reviewed
                                    ? result.review_note ||
                                      "No review note submitted."
                                    : "No review note submitted."}
                            </dd>
                        </div>
                    </dl>
                </section>
            )}
        </div>
    );
}
