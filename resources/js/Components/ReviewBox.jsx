import { useForm } from "@inertiajs/react";
import StatusBadge from "./StatusBadge";

const DECISION_OPTIONS = [
    {
        value: "VALID",
        label: "VALID",
        description: "Accept the result as correct and complete.",
    },
    {
        value: "INVALID",
        label: "INVALID",
        description: "Reject the result and mark it invalid.",
    },
    {
        value: "FLAGGED_FOR_REVIEW",
        label: "FLAGGED FOR REVIEW",
        description: "Keep it pending for follow-up review.",
    },
];

export default function ReviewBox({ result, submitRoute }) {
    const { data, setData, post, processing } = useForm({
        review_note: result.review_note ?? "",
        decision: result.validation_status ?? "VALID",
    });

    if (result.reviewed_at) {
        return (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <h3 className="text-sm font-semibold text-emerald-800">
                    Review information
                </h3>
                <dl className="mt-3 space-y-2 text-sm text-emerald-900">
                    <div>
                        <dt className="font-medium">Current result status</dt>
                        <dd>
                            <StatusBadge status={result.validation_status} />
                        </dd>
                    </div>
                    <div>
                        <dt className="font-medium">Review status</dt>
                        <dd>Reviewed</dd>
                    </div>
                    <div>
                        <dt className="font-medium">Reviewed by</dt>
                        <dd>{result.reviewer?.name ?? "Unknown"}</dd>
                    </div>
                    <div>
                        <dt className="font-medium">Reviewed at</dt>
                        <dd>{new Date(result.reviewed_at).toLocaleString()}</dd>
                    </div>
                    <div>
                        <dt className="font-medium">Review decision</dt>
                        <dd>{result.review_decision ?? "—"}</dd>
                    </div>
                    <div>
                        <dt className="font-medium">Review note</dt>
                        <dd className="whitespace-pre-line">
                            {result.review_note || "No review note submitted."}
                        </dd>
                    </div>
                </dl>
            </div>
        );
    }

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(route(submitRoute, result.id), {
                    preserveScroll: true,
                });
            }}
            className="rounded-lg border bg-white p-4 shadow-sm"
        >
            <div className="mb-3 flex items-center justify-between gap-3">
                <h3 className="font-semibold text-slate-800">
                    Review this record
                </h3>
            </div>

            <dl className="mb-4 space-y-2 rounded-md bg-slate-50 p-3 text-sm text-slate-700">
                <div>
                    <dt className="font-medium">Current result status</dt>
                    <dd>
                        <StatusBadge status={result.validation_status} />
                    </dd>
                </div>
                <div>
                    <dt className="font-medium">Review status</dt>
                    <dd>Not reviewed</dd>
                </div>
                <div>
                    <dt className="font-medium">Review decision</dt>
                    <dd>Not yet decided</dd>
                </div>
            </dl>

            <div className="space-y-2">
                {DECISION_OPTIONS.map((option) => (
                    <label
                        key={option.value}
                        className={`flex cursor-pointer items-start gap-3 rounded-md border p-3 ${data.decision === option.value ? "border-slate-800 bg-slate-50" : "border-slate-200"}`}
                    >
                        <input
                            type="radio"
                            name="decision"
                            value={option.value}
                            checked={data.decision === option.value}
                            onChange={(e) =>
                                setData("decision", e.target.value)
                            }
                            className="mt-1"
                        />
                        <span>
                            <span className="block text-sm font-semibold uppercase text-slate-700">
                                {option.label}
                            </span>
                            <span className="block text-xs text-slate-500">
                                {option.description}
                            </span>
                        </span>
                    </label>
                ))}
            </div>

            <label className="mt-4 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                Review note
            </label>
            <textarea
                value={data.review_note}
                onChange={(e) => setData("review_note", e.target.value)}
                rows={4}
                placeholder="Optional note explaining the decision…"
                className="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"
            />
            <button
                disabled={processing}
                className="mt-3 rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
                Submit Review
            </button>
        </form>
    );
}
