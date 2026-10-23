const COLORS = {
    valid: "#16a34a",
    invalid: "#dc2626",
    flagged: "#d97706",
    pending: "#94a3b8",
};
export default function DonutChart({ data = {}, size = 160 }) {
    const total =
        (data.valid || 0) +
        (data.invalid || 0) +
        (data.flagged || 0) +
        (data.pending || 0);
    let acc = 0;
    const segs = ["valid", "invalid", "flagged", "pending"]
        .map((k) => {
            const v = data[k] || 0;
            const start = total ? (acc / total) * 360 : 0;
            acc += v;
            return `${COLORS[k]} ${start}deg ${total ? (acc / total) * 360 : 0}deg`;
        })
        .join(", ");
    return (
        <div className="flex items-center gap-6">
            <div
                className="relative rounded-full"
                style={{
                    width: size,
                    height: size,
                    background: `conic-gradient(${segs})`,
                }}
            >
                <div className="absolute inset-6 flex items-center justify-center rounded-full bg-white text-center">
                    <div>
                        <p className="text-2xl font-bold">{total}</p>
                        <p className="text-[10px] uppercase text-gray-500">
                            records
                        </p>
                    </div>
                </div>
            </div>
            <ul className="space-y-1 text-sm">
                {["valid", "invalid", "flagged", "pending"].map((k) => (
                    <li key={k} className="flex items-center gap-2">
                        <span
                            className="h-3 w-3 rounded-sm"
                            style={{ background: COLORS[k] }}
                        />
                        <span className="capitalize text-gray-600">{k}</span>
                        <span className="font-semibold">{data[k] || 0}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
