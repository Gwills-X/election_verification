import AppLayout from "../../Components/AppLayout";
import Pagination from "../../Components/Pagination";
import EmptyState from "../../Components/EmptyState";

export default function AuditLogs({ logs }) {
    return (
        <AppLayout title="Audit Logs">
            {logs.data.length === 0 ? (
                <EmptyState
                    title="No audit activity is available"
                    message="Recorded administrative and review actions will appear here."
                />
            ) : (
                <>
                    <div className="overflow-x-auto rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Time</th>
                                    <th className="px-4 py-3">User</th>
                                    <th className="px-4 py-3">Action</th>
                                    <th className="px-4 py-3">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {logs.data.map((l) => (
                                    <tr key={l.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                            {new Date(
                                                l.created_at,
                                            ).toLocaleString()}
                                        </td>
                                        <td className="px-4 py-3">
                                            {l.user?.name ?? "System"}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">
                                                {l.action}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {l.description}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={logs.links} />
                </>
            )}
        </AppLayout>
    );
}
