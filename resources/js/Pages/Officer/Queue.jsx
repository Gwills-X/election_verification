import AppLayout from "../../Components/AppLayout";
import ResultFilters from "../../Components/ResultFilters";
import ResultsTable from "../../Components/ResultsTable";
import Pagination from "../../Components/Pagination";

export default function Queue({ results, filters, elections }) {
    return (
        <AppLayout title="Review Queue">
            <p className="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
                These records are flagged for review and have not been reviewed
                yet.
            </p>
            <ResultFilters
                routeName="officer.queue"
                filters={filters}
                fields={["q", "election_id", "lga", "ward"]}
                options={{ elections }}
            />
            <ResultsTable
                results={results}
                linkPrefix="officer.results"
                returnTo="queue"
                actionLabel="Review"
                emptyTitle="No results require review"
                emptyMessage="Flagged, unreviewed results from your assigned elections will appear here."
            />
            <Pagination links={results.links} />
        </AppLayout>
    );
}
