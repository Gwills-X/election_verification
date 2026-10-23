export default function ReviewBadge({ result }) {
    return result.reviewed_at ? (
        <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
            Reviewed
        </span>
    ) : (
        <span className="text-xs text-gray-400">—</span>
    );
}
