export default function EmptyState({
    title = "Nothing here yet",
    message = "",
}) {
    return (
        <div className="rounded-lg border border-dashed bg-white p-10 text-center">
            <p className="font-medium text-slate-600">{title}</p>
            {message && <p className="mt-1 text-sm text-gray-400">{message}</p>}
        </div>
    );
}
