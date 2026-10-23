import { Link } from "@inertiajs/react";
import StatusBadge from "./StatusBadge";
import ReviewBadge from "./ReviewBadge";
import EmptyState from "./EmptyState";

export default function ResultInfo({
    results = {},
    linkPrefix = "results",
    showElection = true,
}) {
    if (!results.data?.length)
        return (
            <EmptyState
                title="No result records found"
                message="Try adjusting the filters above, or import a dataset first."
            />
        );
    return (
        <div className="overflow-x-auto rounded-lg border bg-white shadow-sm">
            <table className="min-w-full divide-y divide-gray-200 text-sm">
                <thead className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th className="px-4 py-3">PU Code</th>
                        <th className="px-4 py-3">Polling Unit</th>
                        <th className="px-4 py-3">LGA</th>
                        <th className="px-4 py-3">Ward</th>
                        {showElection && (
                            <th className="px-4 py-3">Election</th>
                        )}
                        <th className="px-4 py-3">Source</th>
                        <th className="px-4 py-3">Validation</th>
                        <th className="px-4 py-3">Review</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {results.data.map((r) => (
                        <tr key={r.id} className="hover:bg-gray-50">
                            <td className="px-4 py-3 font-mono text-xs">
                                <Link
                                    href={route(linkPrefix + ".show", r.id)}
                                    className="text-blue-700 hover:underline"
                                >
                                    {r.polling_unit?.code}
                                </Link>
                            </td>
                            <td className="px-4 py-3">
                                {r.polling_unit?.name ?? "—"}
                            </td>
                            <td className="px-4 py-3">
                                {r.polling_unit?.lga ?? "—"}
                            </td>
                            <td className="px-4 py-3">
                                {r.polling_unit?.ward ?? "—"}
                            </td>
                            {showElection && (
                                <td className="px-4 py-3">
                                    {r.election?.name ?? "—"}
                                </td>
                            )}
                            <td className="px-4 py-3">
                                {r.source_type === "SYNTHETIC" ? (
                                    <span className="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        Test data
                                    </span>
                                ) : (
                                    <span className="text-xs text-gray-500">
                                        Real
                                    </span>
                                )}
                            </td>
                            <td className="px-4 py-3">
                                <StatusBadge status={r.validation_status} />
                            </td>
                            <td className="px-4 py-3">
                                <ReviewBadge result={r} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
