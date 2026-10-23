import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import ResultDetails from "../../Components/ResultDetails";

export default function Show({ result }) {
    return (
        <AppLayout
            title={`Result ${result.polling_unit?.code ?? "#" + result.id}`}
        >
            <Link
                href={route("observer.results")}
                className="text-sm text-blue-700 hover:underline"
            >
                ← Back to results
            </Link>
            <div className="mt-4">
                <ResultDetails result={result} includeReview showReviewerName />
            </div>
        </AppLayout>
    );
}
