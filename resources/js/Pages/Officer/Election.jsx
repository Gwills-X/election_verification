import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import StatusBadge from "../../Components/StatusBadge";

export default function Elections({ elections }) {
    return (
        <AppLayout title="My Elections">
            {elections.length === 0 ? (
                <p className="rounded-lg border border-dashed bg-white p-10 text-center text-gray-400">
                    No elections assigned to you yet.
                </p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {elections.map((e) => (
                        <div
                            key={e.id}
                            className="rounded-lg border bg-white p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between">
                                <h4 className="font-semibold text-slate-800">
                                    {e.name}
                                </h4>
                                <StatusBadge status={e.status} />
                            </div>
                            <p className="mt-2 text-sm text-gray-500">
                                {e.election_type?.name} · {e.year} ·{" "}
                                {e.election_type?.scope === "NATIONAL"
                                    ? "National"
                                    : e.election_type?.scope_state?.name}
                            </p>
                            <p className="text-sm text-gray-500">
                                {e.results_count} result records
                            </p>
                            <Link
                                href={route("officer.elections.show", e.id)}
                                className="mt-4 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
                            >
                                Open
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
