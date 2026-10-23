import AppLayout from "../../Components/AppLayout";
import ResultFilters from "../../Components/ResultFilters";
import ResultsTable from "../../Components/ResultsTable";
import Pagination from "../../Components/Pagination";

export default function Results({ results, filters, elections }) {
    return (
        <AppLayout title="Result Records">
            <ResultFilters
                routeName="observer.results"
                filters={filters}
                fields={["q", "election_id", "lga", "ward", "status"]}
                options={{ elections }}
            />
            <ResultsTable
                results={results}
                linkPrefix="observer.results"
                emptyTitle="No published results found"
                emptyMessage="Try changing the election or location filters."
            />
            <Pagination links={results.links} />
        </AppLayout>
    );
}
