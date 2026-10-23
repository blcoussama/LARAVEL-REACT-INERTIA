// Image of a project / task, or a neutral placeholder with the initial when
// no image was uploaded (instead of a broken <img>).
export default function Thumbnail({ src, name = "", className = "" }) {
  if (src) {
    return <img src={src} alt="" className={"object-cover " + className} />;
  }

  return (
    <div
      aria-hidden="true"
      className={
        "flex items-center justify-center bg-gray-700 text-gray-300 font-semibold uppercase " +
        className
      }
    >
      {name.trim().charAt(0) || "?"}
    </div>
  );
}
