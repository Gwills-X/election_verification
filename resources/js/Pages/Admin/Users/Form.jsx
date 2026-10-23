import AppLayout from "../../../Components/AppLayout";
import InputError from "../../../Components/InputError";
import InputLabel from "../../../Components/InputLabel";
import TextInput from "../../../Components/TextInput";
import PrimaryButton from "../../../Components/PrimaryButton";
import Checkbox from "../../../Components/Checkbox";
import { Link, useForm } from "@inertiajs/react";

const field = "mt-1 block w-full";
const lbl = "text-sm font-medium text-gray-700";

export default function Form({ user, roles, elections }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? "",
        email: user?.email ?? "",
        password: "",
        password_confirmation: "",
        role: user?.role ?? "ELECTION_OFFICER",
        is_active: user ? Boolean(user.is_active) : true,
        election_ids: user?.assigned_elections?.map((e) => e.id) ?? [],
    });
    const toggleElection = (id) =>
        setData(
            "election_ids",
            data.election_ids.includes(id)
                ? data.election_ids.filter((x) => x !== id)
                : [...data.election_ids, id],
        );
    const submit = (e) => {
        e.preventDefault();
        user
            ? put(route("admin.users.update", user.id))
            : post(route("admin.users.store"));
    };
    return (
        <AppLayout title={user ? "Edit User" : "Create User"}>
            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 rounded-lg border bg-white p-6 shadow-sm"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Full name" className={lbl} />
                        <TextInput
                            className={field}
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <InputLabel value="Email" className={lbl} />
                        <TextInput
                            type="email"
                            className={field}
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            value={
                                user
                                    ? "New password (leave blank to keep current)"
                                    : "Password"
                            }
                            className={lbl}
                        />
                        <TextInput
                            type="password"
                            className={field}
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                        />
                        <InputError message={errors.password} />
                    </div>
                    <div>
                        <InputLabel value="Confirm password" className={lbl} />
                        <TextInput
                            type="password"
                            className={field}
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData("password_confirmation", e.target.value)
                            }
                        />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Role" className={lbl} />
                        <select
                            className={
                                field +
                                " rounded-md border-gray-300 text-sm shadow-sm"
                            }
                            value={data.role}
                            onChange={(e) => setData("role", e.target.value)}
                        >
                            {roles.map((r) => (
                                <option key={r} value={r}>
                                    {r.replaceAll("_", " ")}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} />
                    </div>
                    <label className="mt-6 flex items-center gap-2 text-sm text-gray-700">
                        <Checkbox
                            checked={data.is_active}
                            onChange={(e) =>
                                setData("is_active", e.target.checked)
                            }
                        />{" "}
                        Account active
                    </label>
                </div>
                <div>
                    <InputLabel
                        value="Assigned elections (Election Officers)"
                        className={lbl}
                    />
                    <div className="mt-2 grid gap-2 rounded-md border p-3 sm:grid-cols-2">
                        {elections.map((e) => (
                            <label
                                key={e.id}
                                className="flex items-center gap-2 text-sm text-gray-700"
                            >
                                <Checkbox
                                    checked={data.election_ids.includes(e.id)}
                                    onChange={() => toggleElection(e.id)}
                                />{" "}
                                {e.name}
                            </label>
                        ))}
                        {elections.length === 0 && (
                            <p className="text-sm text-gray-400">
                                No elections exist yet.
                            </p>
                        )}
                    </div>
                    <InputError message={errors.election_ids} />
                </div>
                <div className="flex gap-3">
                    <PrimaryButton disabled={processing}>
                        {user ? "Save changes" : "Create user"}
                    </PrimaryButton>
                    <Link
                        href={route("admin.users.index")}
                        className="rounded-md border px-4 py-2 text-sm"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
