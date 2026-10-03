import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "bafoundation.totthobox.com",
      },
    ],
  },
};

export default nextConfig;
