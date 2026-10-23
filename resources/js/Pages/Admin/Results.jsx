import AppLayout from "../../Components/AppLayout";
import ResultFilters from "../../Components/ResultFilters";
import ResultsTable from "../../Components/ResultsTable";
import Pagination from "../../Components/Pagination";

export default function Results({
    title = "Result Records",
    routeName = "admin.results",
    results,
    filters,
    elections,
    states,
    lgas,
    wards,
}) {
    return (
        <AppLayout title={title}>
            <ResultFilters
                routeName={routeName}
                filters={filters}
                fields={[
                    "q",
                    "election_id",
                    "state",
                    "lga",
                    "ward",
                    "status",
                    "review",
                ]}
                options={{ elections, states, lgas, wards }}
            />
            <ResultsTable
                results={results}
                linkPrefix="admin.results"
                returnTo={routeName === "admin.flagged" ? "flagged" : "results"}
                actionLabel={routeName === "admin.flagged" ? "Review" : "Open"}
                emptyTitle={
                    routeName === "admin.flagged"
                        ? "No results require review"
                        : "No result records found"
                }
                emptyMessage={
                    routeName === "admin.flagged"
                        ? "Flagged results will appear here when validation identifies a condition for review."
                        : "Try adjusting the filters above, or import a dataset first."
                }
            />
            <Pagination links={results.links} />
        </AppLayout>
    );
}
