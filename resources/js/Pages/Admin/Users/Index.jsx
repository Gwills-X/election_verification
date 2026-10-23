import { Link, router } from "@inertiajs/react";
import AppLayout from "../../../Components/AppLayout";
import Pagination from "../../../Components/Pagination";
import EmptyState from "../../../Components/EmptyState";

const ROLE_BADGE = {
    ADMIN: "bg-purple-100 text-purple-800 ring-purple-600/20",
    ELECTION_OFFICER: "bg-blue-100 text-blue-800 ring-blue-600/20",
    OBSERVER: "bg-teal-100 text-teal-800 ring-teal-600/20",
};

export default function Index({ users }) {
    const destroy = (u) => {
        if (confirm(`Delete account ${u.email}?`))
            router.delete(route("admin.users.destroy", u.id));
    };
    return (
        <AppLayout title="User Management">
            <div className="mb-4 text-right">
                <Link
                    href={route("admin.users.create")}
                    className="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white"
                >
                    Create User
                </Link>
            </div>
            {users.data.length === 0 ? (
                <EmptyState
                    title="No users found"
                    message="Create an officer or observer account to grant access to the system."
                />
            ) : (
                <>
                    <div className="overflow-x-auto rounded-lg border bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th className="px-4 py-3">Email</th>
                                    <th className="px-4 py-3">Role</th>
                                    <th className="px-4 py-3">
                                        Assigned Elections
                                    </th>
                                    <th className="px-4 py-3">Active</th>
                                    <th className="px-4 py-3 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {users.data.map((u) => (
                                    <tr key={u.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium">
                                            {u.name}
                                        </td>
                                        <td className="px-4 py-3">{u.email}</td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${ROLE_BADGE[u.role] ?? "bg-gray-100 text-gray-700"}`}
                                            >
                                                {u.role.replaceAll("_", " ")}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-xs text-gray-500">
                                            {u.assigned_elections
                                                ?.map((e) => e.name)
                                                .join(", ") || "—"}
                                        </td>
                                        <td className="px-4 py-3">
                                            {u.is_active ? (
                                                <span className="text-green-600">
                                                    Yes
                                                </span>
                                            ) : (
                                                <span className="text-red-600">
                                                    No
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right text-xs">
                                            {u.role !== "ADMIN" && (
                                                <>
                                                    <Link
                                                        href={route(
                                                            "admin.users.edit",
                                                            u.id,
                                                        )}
                                                        className="text-blue-700 hover:underline"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <button
                                                        onClick={() =>
                                                            destroy(u)
                                                        }
                                                        className="ml-3 text-red-700 hover:underline"
                                                    >
                                                        Delete
                                                    </button>
                                                </>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={users.links} />
                </>
            )}
        </AppLayout>
    );
}
