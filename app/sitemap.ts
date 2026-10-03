import type { MetadataRoute } from "next";

const siteUrl = "https://bafoundation.totthobox.com";

const publicRoutes = [
  "",
  "/about",
  "/activities",
  "/works",
  "/how-it-works",
  "/members",
  "/contact",
  "/donate",
];

export default function sitemap(): MetadataRoute.Sitemap {
  return publicRoutes.map((path) => ({
    url: `${siteUrl}${path}`,
  }));
}
