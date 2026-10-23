import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";

const NAV = {
    ADMIN: [
        { route: "admin.dashboard", label: "Dashboard" },
        {
            route: "admin.elections.index",
            label: "Elections",
            activeRoutes: ["admin.elections.*"],
        },
        { route: "admin.import", label: "Import Results" },
        {
            route: "admin.results",
            label: "All Results",
            activeRoutes: ["admin.results", "admin.results.show"],
        },
        { route: "admin.flagged", label: "Flagged Results" },
        {
            route: "admin.reports.index",
            label: "Reports",
            activeRoutes: ["admin.reports.*"],
        },
        {
            route: "admin.users.index",
            label: "Users",
            activeRoutes: ["admin.users.*"],
        },
        { route: "admin.audit", label: "Audit Logs" },
    ],
    ELECTION_OFFICER: [
        { route: "officer.dashboard", label: "Dashboard" },
        {
            route: "officer.elections",
            label: "Assigned Elections",
            activeRoutes: ["officer.elections", "officer.elections.show"],
        },
        {
            route: "officer.results",
            label: "All Results",
            activeRoutes: ["officer.results", "officer.results.show"],
        },
        { route: "officer.queue", label: "Review Queue" },
        {
            route: "officer.reports.index",
            label: "Reports",
            activeRoutes: ["officer.reports.*"],
            excludeExportAnchor: true,
        },
        {
            route: "officer.reports.index",
            label: "Export",
            href: `${route("officer.reports.index")}#downloads`,
            exportAnchor: true,
        },
    ],
    OBSERVER: [
        { route: "observer.dashboard", label: "Dashboard" },
        {
            route: "observer.elections",
            label: "Elections",
            activeRoutes: ["observer.elections", "observer.elections.show"],
        },
        {
            route: "observer.results",
            label: "Results",
            activeRoutes: ["observer.results", "observer.results.show"],
        },
    ],
};

const isActive = (item, exportAnchorActive) => {
    try {
        const matchesRoute = (item.activeRoutes ?? [item.route]).some((name) =>
            route().current(name),
        );
        if (item.exportAnchor) return matchesRoute && exportAnchorActive;
        if (item.excludeExportAnchor && exportAnchorActive) return false;

        return matchesRoute;
    } catch {
        return false;
    }
};

export default function AppLayout({ title, children }) {
    const page = usePage();
    const { auth, flash } = page.props;
    const user = auth?.user;
    const [menuOpen, setMenuOpen] = useState(false);
    const [exportAnchorActive, setExportAnchorActive] = useState(
        () =>
            typeof window !== "undefined" &&
            window.location.hash === "#downloads",
    );
    useEffect(() => {
        setExportAnchorActive(
            typeof window !== "undefined" &&
                window.location.hash === "#downloads",
        );
    }, [page.url]);
    const navigation = NAV[user?.role] ?? [];
    const logout = (e) => {
        e.preventDefault();
        router.post(route("logout"));
    };
    return (
        <div className="flex h-dvh min-h-0 flex-col overflow-hidden bg-gray-100">
            <header className="z-50 flex h-16 shrink-0 items-center gap-3 border-b bg-white px-4 sm:px-6">
                <button
                    type="button"
                    aria-label={
                        menuOpen ? "Close navigation" : "Open navigation"
                    }
                    aria-expanded={menuOpen}
                    aria-controls="app-sidebar"
                    onClick={() => setMenuOpen(!menuOpen)}
                    className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 lg:hidden"
                >
                    {menuOpen ? "Close" : "Menu"}
                </button>
                <h1 className="min-w-0 flex-1 truncate text-lg font-semibold text-slate-800 sm:text-xl">
                    {title}
                </h1>
                <div className="flex shrink-0 items-center gap-3 text-sm sm:gap-4">
                    <Link
                        href={route("profile.edit")}
                        className="rounded-sm text-slate-600 transition hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600"
                    >
                        Profile
                    </Link>
                    <a
                        href="#"
                        onClick={logout}
                        className="rounded-sm text-slate-600 transition hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600"
                    >
                        Log out
                    </a>
                </div>
            </header>
            <div className="relative flex min-h-0 flex-1">
                {menuOpen && (
                    <button
                        type="button"
                        aria-label="Close navigation"
                        onClick={() => setMenuOpen(false)}
                        className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
                    />
                )}
                <aside
                    id="app-sidebar"
                    className={`fixed inset-y-16 left-0 z-40 flex h-[calc(100dvh-4rem)] w-72 flex-col bg-slate-900 text-slate-300 shadow-xl transition-transform duration-200 lg:static lg:h-full lg:w-64 lg:shrink-0 lg:translate-x-0 lg:shadow-none ${menuOpen ? "translate-x-0" : "-translate-x-full"}`}
                >
                    <div className="px-6 py-5">
                        <Link
                            href={route("home")}
                            onClick={() => setMenuOpen(false)}
                            className="text-lg font-bold text-white"
                        >
                            Election Verification
                        </Link>
                        <p className="mt-1 text-xs text-slate-400">
                            Management &amp; Public Verification
                        </p>
                    </div>
                    <nav
                        aria-label="Main navigation"
                        className="flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-2"
                    >
                        {navigation.map((item) => {
                            const active = isActive(item, exportAnchorActive);
                            const href = item.href ?? route(item.route);

                            return (
                                <Link
                                    key={`${item.route}-${item.label}`}
                                    href={href}
                                    onClick={() => {
                                        setMenuOpen(false);
                                        setExportAnchorActive(
                                            Boolean(item.exportAnchor),
                                        );
                                    }}
                                    aria-current={active ? "page" : undefined}
                                    className={`block rounded-r-md border-l-4 px-3 py-2 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-300 ${active ? "border-emerald-400 bg-slate-800 font-semibold text-white" : "border-transparent font-medium text-slate-300 hover:bg-slate-800/70 hover:text-white"}`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                    <div className="shrink-0 border-t border-slate-800 px-4 py-3 text-xs text-slate-400">
                        Signed in as{" "}
                        <span className="font-medium text-slate-200">
                            {user?.name}
                        </span>
                        <span className="mt-1 block w-fit rounded bg-slate-800 px-2 py-0.5 text-[10px] uppercase tracking-wide">
                            {user?.role?.replaceAll("_", " ")}
                        </span>
                    </div>
                </aside>
                <main className="min-w-0 flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6">
                    {flash?.success && (
                        <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {flash.error}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
