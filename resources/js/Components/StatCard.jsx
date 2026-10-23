export default function StatCard({ label, value, tone = "slate" }) {
    const tones = {
        slate: "text-slate-700",
        green: "text-green-600",
        red: "text-red-600",
        amber: "text-amber-600",
        blue: "text-blue-600",
    };
    return (
        <div className="rounded-lg border bg-white p-4 shadow-sm">
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                {label}
            </p>
            <p
                className={`mt-1 text-2xl font-bold ${tones[tone] ?? tones.slate}`}
            >
                {value ?? 0}
            </p>
        </div>
    );
}
