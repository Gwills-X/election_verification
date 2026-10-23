export default function BarList({ data = {}, title }) {
    const entries = Object.entries(data);
    const max = Math.max(1, ...entries.map(([, v]) => v));
    return (
        <div className="rounded-lg border bg-white p-4 shadow-sm">
            {title && (
                <h3 className="mb-3 text-sm font-semibold text-slate-700">
                    {title}
                </h3>
            )}
            {entries.length === 0 ? (
                <p className="text-sm text-gray-400">
                    No candidate/party data available yet.
                </p>
            ) : (
                <ul className="space-y-2">
                    {entries.map(([k, v]) => (
                        <li key={k}>
                            <div className="mb-0.5 flex justify-between text-sm">
                                <span className="font-medium">{k}</span>
                                <span>{Number(v).toLocaleString()}</span>
                            </div>
                            <div className="h-2 rounded bg-gray-100">
                                <div
                                    className="h-2 rounded bg-slate-700"
                                    style={{ width: `${(v / max) * 100}%` }}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
