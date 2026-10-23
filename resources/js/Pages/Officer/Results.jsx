import AppLayout from "../../Components/AppLayout";
import ResultFilters from "../../Components/ResultFilters";
import ResultsTable from "../../Components/ResultsTable";
import Pagination from "../../Components/Pagination";

export default function Results({ results, filters, elections, lgas, wards }) {
    return (
        <AppLayout title="Result Records">
            <ResultFilters
                routeName="officer.results"
                filters={filters}
                fields={["q", "election_id", "lga", "ward", "status", "review"]}
                options={{ elections, lgas, wards }}
            />
            <ResultsTable
                results={results}
                linkPrefix="officer.results"
                returnTo="results"
                emptyMessage="No results match these filters within your assigned elections."
            />
            <Pagination links={results.links} />
        </AppLayout>
    );
}
