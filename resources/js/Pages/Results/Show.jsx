import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Components/PublicLayout';
import ResultDetails from '@/Components/ResultDetails';
export default function Show({ result }) {
    return (
        <PublicLayout>
            <Head title="Result details" />
            <Link href={route('results.index')} className="text-sm text-indigo-700">&larr; Back to results</Link>
            <h1 className="mb-4 mt-2 text-2xl font-bold">{result.polling_unit?.name ?? result.polling_unit?.code}</h1>
            <ResultDetails result={result} />
        </PublicLayout>
    );
}
