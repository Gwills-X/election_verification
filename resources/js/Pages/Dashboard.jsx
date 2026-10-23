import { Head, Link } from "@inertiajs/react";
import PublicLayout from "@/Components/PublicLayout";
const Stat = ({ label, value }) => (
    <div className="rounded-lg border bg-white p-5">
        <div className="text-sm text-gray-500">{label}</div>
        <div className="mt-1 text-3xl font-bold">{value.toLocaleString()}</div>
    </div>
);
export default function Dashboard({ stats, elections }) {
    return (
        <PublicLayout>
            <Head title="Election Result Verification" />
            <h1 className="text-2xl font-bold">
                Election Result Validation and Public Verification
            </h1>
            <p className="mt-1 text-gray-600">
                {elections.length
                    ? elections.map((e) => `${e.name}`).join(" · ")
                    : "No dataset has been imported yet."}
            </p>
            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Stat label="Total records" value={stats.total} />
                <Stat label="Valid records" value={stats.valid} />
                <Stat label="Invalid records" value={stats.invalid} />
                <Stat label="Flagged for review" value={stats.flagged} />
            </div>
            <Link
                href={route("results.index")}
                className="mt-8 inline-block rounded-md bg-slate-800 px-5 py-2.5 text-white"
            >
                Browse results
            </Link>
        </PublicLayout>
    );
}
