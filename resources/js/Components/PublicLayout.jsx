import { Link, usePage } from "@inertiajs/react";
export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    return (
        <div className="flex min-h-screen flex-col bg-gray-50 text-slate-900">
            <header className="bg-slate-900 text-white">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4">
                    <Link href={route("home")} className="font-semibold">
                        Election Result Verification
                    </Link>
                    <nav
                        aria-label="Public navigation"
                        className="flex flex-wrap gap-x-4 gap-y-2 text-xs sm:text-sm"
                    >
                        <Link
                            href={route("home")}
                            className="rounded-sm hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        >
                            Home
                        </Link>
                        <Link
                            href={route("verify.index")}
                            className="rounded-sm hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        >
                            Verify a Result
                        </Link>
                        <Link
                            href={route("results.index")}
                            className="rounded-sm hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                        >
                            Browse Results
                        </Link>
                        {auth?.user ? (
                            <Link
                                href={route("dashboard")}
                                className="rounded-sm hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <Link
                                href={route("login")}
                                className="rounded-sm hover:text-slate-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
                            >
                                Staff Login
                            </Link>
                        )}
                    </nav>
                </div>
            </header>
            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                {children}
            </main>
            <footer className="border-t bg-white py-4 text-center text-xs text-gray-500">
                This system organises election information and identifies data
                inconsistencies. It does not determine whether an election was
                free, fair, fraudulent or otherwise legitimate.
            </footer>
        </div>
    );
}
