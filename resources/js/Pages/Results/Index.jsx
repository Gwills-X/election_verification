import { Head } from '@inertiajs/react';
import PublicLayout from '@/Components/PublicLayout';
import FilterBar from '@/Components/FilterBar';
import ResultsTable from '@/Components/ResultsTable';
export default function Index({ results, filters, lgas, wards }) {
    return (
        <PublicLayout>
            <Head title="Results" />
            <h1 className="mb-4 text-2xl font-bold">Election results</h1>
            <FilterBar routeName="results.index" filters={filters} lgas={lgas} wards={wards} />
            <ResultsTable results={results} showRoute="results.show" />
        </PublicLayout>
    );
}
