import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link } from "@inertiajs/react";
import TasksTable from "../Task/TasksTable";

export default function Show({ user, tasks, queryParams }) {
  return (
    <AuthenticatedLayout
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {`User "${user.name}"`}
          </h2>
          {user.can.update && (
            <Link
              href={route("user.edit", user.id)}
              className="bg-emerald-500 py-1 px-3 text-white rounded-sm shadow-sm transition-all hover:bg-emerald-600"
            >
              Edit
            </Link>
          )}
        </div>
      }
    >
      <Head title={`User "${user.name}"`} />
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
            <div className="p-6 text-gray-900 dark:text-gray-100">
              <div className="grid gap-1 grid-cols-2 mt-2">
                <div>
                  <div>
                    <label className="font-bold text-lg">User ID</label>
                    <p className="mt-1">{user.id}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Name</label>
                    <p className="mt-1">{user.name}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Email</label>
                    <p className="mt-1">
                      {user.email}
                      {!user.email_verified && (
                        <span className="ms-2 text-sm text-amber-500">
                          (not verified)
                        </span>
                      )}
                    </p>
                  </div>
                </div>
                <div>
                  <div>
                    <label className="font-bold text-lg">Role</label>
                    <p className="mt-1">
                      <span
                        className={
                          "px-2 py-1 rounded-sm text-white " +
                          (user.role === "admin"
                            ? "bg-indigo-600"
                            : "bg-gray-600")
                        }
                      >
                        {user.role === "admin" ? "Admin" : "Member"}
                      </span>
                    </p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Member since</label>
                    <p className="mt-1">{user.created_at}</p>
                  </div>
                  <div className="mt-4">
                    <label className="font-bold text-lg">Activity</label>
                    <p className="mt-1">
                      {user.projects_count} project(s) owned,{" "}
                      {user.assigned_tasks_count} task(s) assigned
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="pb-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg">
            <div className="p-6 text-gray-900 dark:text-gray-100">
              <h3 className="text-xl font-semibold mb-4">Assigned tasks</h3>
              <TasksTable
                tasks={tasks}
                queryParams={queryParams}
                hideAssigneeColumn={true}
                routeName="user.show"
                routeParams={{ user: user.id }}
              />
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
