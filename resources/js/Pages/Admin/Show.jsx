import { Link } from "@inertiajs/react";
import AppLayout from "../../Components/AppLayout";
import ResultDetails from "../../Components/ResultDetails";
import ReviewBox from "../../Components/ReviewBox";

export default function Show({ result, backUrl }) {
    return (
        <AppLayout
            title={`Result ${result.polling_unit?.code ?? "#" + result.id}`}
        >
            <Link
                href={backUrl ?? route("admin.results")}
                className="text-sm text-blue-700 hover:underline"
            >
                ← Back to results
            </Link>
            <div className="mt-4 grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <ResultDetails result={result} />
                </div>
                <div>
                    <ReviewBox
                        result={result}
                        submitRoute="admin.results.review"
                    />
                </div>
            </div>
        </AppLayout>
    );
}
