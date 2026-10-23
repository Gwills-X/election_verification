const MAP = {
    VALID: ["Valid", "bg-green-100 text-green-800 ring-green-600/20"],
    INVALID: ["Invalid", "bg-red-100 text-red-800 ring-red-600/20"],
    FLAGGED_FOR_REVIEW: [
        "Flagged for review",
        "bg-amber-100 text-amber-900 ring-amber-600/30",
    ],
    PENDING: ["Pending", "bg-gray-100 text-gray-700 ring-gray-500/20"],
    NOT_APPLICABLE: [
        "Not applicable",
        "bg-slate-100 text-slate-500 ring-slate-400/20",
    ],
    DRAFT: ["Draft", "bg-gray-100 text-gray-600 ring-gray-500/20"],
    ACTIVE: ["Active", "bg-blue-100 text-blue-800 ring-blue-600/20"],
    COMPLETED: ["Completed", "bg-green-100 text-green-800 ring-green-600/20"],
    ARCHIVED: ["Archived", "bg-slate-200 text-slate-600 ring-slate-500/20"],
};
export default function StatusBadge({ status }) {
    const [label, cls] = MAP[status] ?? [
        status,
        "bg-gray-100 text-gray-700 ring-gray-500/20",
    ];
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${cls}`}
        >
            {label}
        </span>
    );
}
