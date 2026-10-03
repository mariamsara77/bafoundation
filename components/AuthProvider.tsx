"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import {
  API_BASE_URL,
  ApiError,
  clearStoredToken,
  getMe,
  getStoredToken,
  storeToken,
  login as loginApi,
  logout as logoutApi,
  register as registerApi,
} from "@/lib/api";
import type { AuthUser } from "@/lib/api";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<AuthUser | null>;
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<AuthUser>;
  isMember: boolean;
  isAdmin: boolean;
  googleLogin: () => Promise<AuthUser>;
  logout: (all?: boolean) => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export default function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    const token = getStoredToken();
    if (!token) {
      setUser(null);
      return;
    }

    try {
      const freshUser = await getMe();
      setUser(freshUser);
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        clearStoredToken();
        setUser(null);
      }
    }
  }, []);

  useEffect(() => {
    refresh().finally(() => setLoading(false));
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    const result = await loginApi(email, password);
    if (!result.user) {
      await refresh();
      throw new ApiError("লগইন সফল হয়নি। আবার চেষ্টা করুন।", 500);
    }

    setUser(result.user);
    // Re-read the authenticated account so the profile menu always reflects
    // the current backend state immediately after login.
    await refresh();
    return result.user;
  }, [refresh]);

  const register = useCallback(async (
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ) => {
    const result = await registerApi(name, email, password, passwordConfirmation);
    if (!result.user) {
      await refresh();
      throw new ApiError("রেজিস্ট্রেশন সফল হয়নি। আবার চেষ্টা করুন।", 500);
    }

    setUser(result.user);
    // Registration creates the member profile; refresh ensures the navbar/profile
    // menu uses the same canonical user state returned by /auth/me.
    await refresh();
    return result.user;
  }, [refresh]);

  const googleLogin = useCallback(() => {
    return new Promise<AuthUser>((resolve, reject) => {
      const width = 520;
      const height = 700;
      const left = Math.max(0, window.screenX + (window.outerWidth - width) / 2);
      const top = Math.max(0, window.screenY + (window.outerHeight - height) / 2);
      const popup = window.open(
        API_BASE_URL + "/api/auth/google/redirect",
        "bafoundation-google-login",
        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`,
      );

      if (!popup) {
        reject(new ApiError("Google লগইনের জন্য popup খুলতে পারেনি। Browser popup permission চালু করুন।", 400));
        return;
      }

      let settled = false;
      const cleanup = () => {
        window.removeEventListener("message", onMessage);
        window.clearInterval(poll);
        window.clearTimeout(timeout);
      };
      const finish = (callback: () => void) => {
        if (settled) return;
        settled = true;
        cleanup();
        callback();
      };

      const onMessage = async (event: MessageEvent) => {
        if (event.origin !== window.location.origin) return;
        const data = event.data as { type?: string; user?: AuthUser; token?: string; message?: string } | null;
        if (!data || !data.type?.startsWith("bafoundation-google-auth")) return;

        if (data.type === "bafoundation-google-auth-error") {
          finish(() => reject(new ApiError(data.message || "Google লগইন সম্পন্ন করা যায়নি।", 400)));
          return;
        }

        if (data.type === "bafoundation-google-auth" && data.user && data.token) {
          try {
            storeToken(data.token);
            await refresh();
            const freshUser = getStoredToken() ? await getMe() : data.user;
            setUser(freshUser);
            finish(() => resolve(freshUser));
          } catch {
            setUser(data.user);
            finish(() => resolve(data.user));
          }
        }
      };

      window.addEventListener("message", onMessage);

      const poll = window.setInterval(() => {
        if (popup.closed && !settled) {
          finish(() => reject(new ApiError("Google লগইন উইন্ডোটি বন্ধ করা হয়েছে।", 400)));
        }
      }, 500);

      const timeout = window.setTimeout(() => {
        finish(() => reject(new ApiError("Google লগইন সম্পন্ন হতে বেশি সময় লাগছে। আবার চেষ্টা করুন।", 408)));
        if (!popup.closed) popup.close();
      }, 120000);
    });
  }, [refresh]);

  const logout = useCallback(async (all = false) => {
    await logoutApi(all);
    setUser(null);
  }, []);

  const roles = user?.roles ?? [];
  const isMember = user?.is_member === true;
  const isAdmin = roles.includes("admin");
  const value = useMemo(
    () => ({ user, loading, login, register, googleLogin, logout, refresh, isMember, isAdmin }),
    [user, loading, login, register, googleLogin, logout, refresh, isMember, isAdmin],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used inside AuthProvider");
  return value;
}
