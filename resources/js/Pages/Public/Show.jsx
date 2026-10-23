import { Link } from "@inertiajs/react";
import PublicLayout from "../../Components/PublicLayout";
import ResultDetails from "../../Components/ResultDetails";

export default function Show({ result }) {
    return (
        <PublicLayout>
            <Link
                href={route("verify.index", {
                    election_id: result.election_id,
                })}
                className="text-sm text-blue-700 hover:underline"
            >
                ← Back to search
            </Link>
            <h1 className="mb-6 mt-2 text-2xl font-bold text-slate-800">
                Polling-Unit Result
            </h1>
            <ResultDetails result={result} includeReview />
        </PublicLayout>
    );
}
