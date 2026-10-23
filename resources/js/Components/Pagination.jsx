import { Link } from "@inertiajs/react";

// Laravel sends "&laquo; Previous" / "Next &raquo;": decode these two known
// entities and render the label as plain text (no dangerouslySetInnerHTML).
const decodeLabel = (label) =>
  String(label).replace("&laquo;", "«").replace("&raquo;", "»");

export default function Pagination({ links }) {
  if (!links || links.length <= 3) {
    return null;
  }

  return (
    <nav className="text-center mt-4" aria-label="Pagination">
      {links.map((link, index) => (
        <Link
          preserveScroll
          href={link.url || ""}
          key={index}
          aria-current={link.active ? "page" : undefined}
          className={
            "inline-block py-2 px-3 rounded-lg text-gray-200 text-xs " +
            (link.active ? "bg-gray-950 " : " ") +
            (!link.url
              ? "text-gray-500! cursor-not-allowed pointer-events-none "
              : "hover:bg-gray-950")
          }
        >
          {decodeLabel(link.label)}
        </Link>
      ))}
    </nav>
  );
}
