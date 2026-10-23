import Pagination from "@/Components/Pagination";
import SelectInput from "@/Components/SelectInput";
import TextInput from "@/Components/TextInput";
import TableHeading from "@/Components/TableHeading";
import Thumbnail from "@/Components/Thumbnail";
import {
  TASK_PRIORITY_CLASS_MAP,
  TASK_PRIORITY_TEXT_MAP,
  TASK_STATUS_CLASS_MAP,
  TASK_STATUS_TEXT_MAP,
} from "@/constants.js";
import { Link, router } from "@inertiajs/react";

/**
 * Table of tasks shared by the task list, "My Tasks", the project page and
 * the user page. Filters and sorting reload the page that hosts the table:
 * pass its route with `routeName` / `routeParams`.
 */
export default function TasksTable({
  tasks,
  success,
  queryParams = null,
  hideProjectColumn = false,
  hideAssigneeColumn = false,
  routeName = "task.index",
  routeParams = {},
}) {
  queryParams = { ...(queryParams || {}) };
  delete queryParams.page;

  const reload = () => {
    router.get(route(routeName, routeParams), queryParams, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const searchFieldChanged = (name, value) => {
    if (value) {
      queryParams[name] = value;
    } else {
      delete queryParams[name];
    }

    reload();
  };

  const onKeyPress = (name, e) => {
    if (e.key !== "Enter") return;

    searchFieldChanged(name, e.target.value);
  };

  const sortChanged = (name) => {
    if (name === queryParams.sort_field) {
      queryParams.sort_direction =
        queryParams.sort_direction === "asc" ? "desc" : "asc";
    } else {
      queryParams.sort_field = name;
      queryParams.sort_direction = "asc";
    }
    reload();
  };

  const deleteTask = (task) => {
    if (!window.confirm("Are you sure you want to delete the task?")) {
      return;
    }
    router.delete(route("task.destroy", task.id));
  };

  const columnCount =
    9 - (hideProjectColumn ? 1 : 0) - (hideAssigneeColumn ? 1 : 0);

  return (
    <>
      {success && (
        <div className="bg-emerald-500 py-2 px-4 text-white rounded-sm mb-4">
          {success}
        </div>
      )}
      <div className="overflow-auto">
        <table className="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
          <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b-2 border-gray-500">
            <tr className="text-nowrap">
              <TableHeading
                name="id"
                sort_field={queryParams.sort_field}
                sort_direction={queryParams.sort_direction}
                sortChanged={sortChanged}
              >
                ID
              </TableHeading>
              <th className="px-3 py-3">Image</th>
              {!hideProjectColumn && (
                <th className="px-3 py-3">Project Name</th>
              )}
              <TableHeading
                name="name"
                sort_field={queryParams.sort_field}
                sort_direction={queryParams.sort_direction}
                sortChanged={sortChanged}
              >
                Name
              </TableHeading>

              <TableHeading
                name="status"
                sort_field={queryParams.sort_field}
                sort_direction={queryParams.sort_direction}
                sortChanged={sortChanged}
              >
                Status
              </TableHeading>

              <TableHeading
                name="priority"
                sort_field={queryParams.sort_field}
                sort_direction={queryParams.sort_direction}
                sortChanged={sortChanged}
              >
                Priority
              </TableHeading>

              <TableHeading
                name="due_date"
                sort_field={queryParams.sort_field}
                sort_direction={queryParams.sort_direction}
                sortChanged={sortChanged}
              >
                Due Date
              </TableHeading>
              {!hideAssigneeColumn && (
                <th className="px-3 py-3">Assigned To</th>
              )}
              <th className="px-3 py-3">Created By</th>
              <th className="px-3 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <thead className="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b-2 border-gray-500">
            <tr className="text-nowrap">
              <th className="px-3 py-3"></th>
              <th className="px-3 py-3"></th>
              {!hideProjectColumn && <th className="px-3 py-3"></th>}
              <th className="px-3 py-3">
                <TextInput
                  className="w-full"
                  defaultValue={queryParams.name}
                  placeholder="Task Name"
                  onBlur={(e) => searchFieldChanged("name", e.target.value)}
                  onKeyPress={(e) => onKeyPress("name", e)}
                />
              </th>
              <th className="px-3 py-3">
                <SelectInput
                  className="w-full min-w-40"
                  defaultValue={queryParams.status}
                  onChange={(e) => searchFieldChanged("status", e.target.value)}
                >
                  <option value="">Select Status</option>
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </SelectInput>
              </th>
              <th className="px-3 py-3"></th>
              <th className="px-3 py-3"></th>
              {!hideAssigneeColumn && <th className="px-3 py-3"></th>}
              <th className="px-3 py-3"></th>
              <th className="px-3 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {tasks.data.length === 0 && (
              <tr>
                <td colSpan={columnCount} className="px-3 py-6 text-center">
                  No tasks found.
                </td>
              </tr>
            )}
            {tasks.data.map((task) => (
              <tr
                className="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                key={task.id}
              >
                <td className="px-3 py-2">{task.id}</td>
                <td className="px-3 py-2">
                  <Thumbnail
                    src={task.image_path}
                    name={task.name}
                    className="w-14 h-10 rounded-sm"
                  />
                </td>
                {!hideProjectColumn && (
                  <td className="px-3 py-2 hover:underline">
                    <Link href={route("project.show", task.project.id)}>
                      {task.project.name}
                    </Link>
                  </td>
                )}
                <th className="px-3 py-2 text-gray-100 hover:underline">
                  <Link href={route("task.show", task.id)}>{task.name}</Link>
                </th>
                <td className="px-3 py-2">
                  <span
                    className={
                      "px-2 py-1 rounded-sm text-nowrap text-white " +
                      TASK_STATUS_CLASS_MAP[task.status]
                    }
                  >
                    {TASK_STATUS_TEXT_MAP[task.status]}
                  </span>
                </td>
                <td className="px-3 py-2">
                  <span
                    className={
                      "px-2 py-1 rounded-sm text-nowrap text-white " +
                      TASK_PRIORITY_CLASS_MAP[task.priority]
                    }
                  >
                    {TASK_PRIORITY_TEXT_MAP[task.priority]}
                  </span>
                </td>
                <td className="px-3 py-2 text-nowrap">{task.due_date}</td>
                {!hideAssigneeColumn && (
                  <td className="px-3 py-2 text-nowrap">
                    {task.assignedUser?.name ?? "Unassigned"}
                  </td>
                )}
                <td className="px-3 py-2 text-nowrap">
                  {task.createdBy?.name ?? "Deleted user"}
                </td>
                <td className="px-3 py-2 text-nowrap text-right">
                  {task.can.update && (
                    <Link
                      href={route("task.edit", task.id)}
                      className="font-medium text-blue-600 dark:text-blue-500 hover:underline mx-1"
                    >
                      Edit
                    </Link>
                  )}
                  {task.can.delete && (
                    <button
                      onClick={() => deleteTask(task)}
                      className="font-medium text-red-600 dark:text-red-500 hover:underline mx-1"
                    >
                      Delete
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Pagination links={tasks.meta.links} />
    </>
  );
}
