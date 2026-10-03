import type { Metadata, Viewport } from "next";
import { Hind_Siliguri } from "next/font/google";
import "./globals.css";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import AuthProvider from "@/components/AuthProvider";
import VisitorTracker from "@/components/VisitorTracker";
import PWARegister from "@/components/PWARegister";

const hindSiliguri = Hind_Siliguri({
  weight: ["300", "400", "500", "600", "700"],
  subsets: ["bengali"],
  display: "swap",
});

const siteName = "বন্ধু আদর্শ ফাউন্ডেশন";
const siteDescription = "মানুষের পাশে থেকে একটি সুন্দর, মানবিক ও সম্ভাবনাময় ভবিষ্যৎ গড়ার উদ্যোগ।";
const siteUrl = "https://bafoundation.totthobox.com";
const socialImage = {
  url: "/500.png",
  width: 500,
  height: 500,
  alt: siteName,
};

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: {
    default: siteName,
    template: "%s | বন্ধু আদর্শ ফাউন্ডেশন",
  },
  description: siteDescription,
  applicationName: siteName,
  alternates: {
    canonical: "/",
  },
  robots: { index: true, follow: true },
  openGraph: {
    type: "website",
    locale: "bn_BD",
    url: siteUrl,
    siteName,
    title: siteName,
    description: siteDescription,
    images: [socialImage],
  },
  twitter: {
    card: "summary_large_image",
    title: siteName,
    description: siteDescription,
    images: [socialImage],
  },
  appleWebApp: {
    capable: true,
    title: siteName,
    statusBarStyle: "default",
  },
  icons: {
    icon: [{ url: "/500.png", sizes: "500x500", type: "image/png" }],
    apple: [{ url: "/500.png", sizes: "500x500", type: "image/png" }],
  },
};

export const viewport: Viewport = {
  themeColor: "#FF4500",
  colorScheme: "light",
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="bn" className="scroll-smooth">
      <body
        className={`${hindSiliguri.className} min-h-screen bg-zinc-50 text-zinc-900 antialiased`}
      >
        <AuthProvider>
          <VisitorTracker />
          <PWARegister />
          <Navbar />
          <main className="min-h-[calc(100vh-8rem)]">{children}</main>
          <Footer />
        </AuthProvider>
      </body>
    </html>
  );
}
