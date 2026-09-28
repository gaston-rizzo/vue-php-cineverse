/* ============================================================================
 * SERVICE: auth.service.ts
 * ============================================================================
 *
 * Servicio para manejar la autenticación de usuarios: registro, login,
 * verificación de email, recuperación de contraseña y cierre de sesión,
 * consumiendo la API propia del backend.
 * ============================================================================ */

import backendApi from "@/core/api/backendApi";

import type {
  LoginPayload,
  RegisterPayload,
  ResendVerificationEmailPayload,
  ForgotPasswordPayload,
  ResetPasswordPayload,
  LoginResponse,
  AuthenticatedUserResponse,
  VerifyEmailResponse,
  LogoutResponse,
} from "@/features/auth/types/auth";

/**
 * Registra un nuevo usuario en el backend.
 *
 * El backend envía un email de verificación en el idioma indicado
 * en el payload.
 */
export const registerUser = async (payload: RegisterPayload) => {
  const { data } = await backendApi.post("/register", payload);
  return data;
};

/**
 * Verifica el email de un usuario a partir del token recibido por correo.
 */
export const verifyEmail = async (token: string): Promise<VerifyEmailResponse> => {
  const { data } = await backendApi.get("/verify-email", { params: { token } });
  return data;
};

/**
 * Inicia sesión con las credenciales del usuario.
 *
 * Devuelve el usuario autenticado junto con el token CSRF necesario
 * para requests posteriores (ej: logout).
 */
export const loginUser = async (payload: LoginPayload): Promise<LoginResponse> => {
  const { data } = await backendApi.post<LoginResponse>("/login", payload);
  return data;
};

/**
 * Obtiene el usuario actualmente autenticado según la sesión activa.
 */
export const getAuthenticatedUser = async (): Promise<AuthenticatedUserResponse> => {
  const { data } = await backendApi.get<AuthenticatedUserResponse>("/user");
  return data;
};

/**
 * Reenvía el email de verificación de cuenta a un usuario no verificado.
 */
export const resendVerificationEmail = async (payload: ResendVerificationEmailPayload) => {
  const { data } = await backendApi.post("/resend-verification-email", payload);
  return data;
};

/**
 * Solicita el envío de un email para recuperar la contraseña.
 */
export const forgotPassword = async (payload: ForgotPasswordPayload) => {
  const { data } = await backendApi.post("/forgot-password", payload);
  return data;
};

/**
 * Valida un token de recuperación de contraseña contra el backend, sin
 * modificar la contraseña. Se usa al montar ResetPasswordView para saber
 * de antemano si el link del email sigue siendo válido, antes de mostrarle
 * el formulario al usuario.
 */
export const validateResetToken = async (token: string) => {
  const { data } = await backendApi.get("/reset-password/validate", {
    params: { token }
  });
  return data;
};

/**
 * Establece una nueva contraseña a partir del token de recuperación
 * recibido por email.
 */
export const resetPassword = async (payload: ResetPasswordPayload) => {
  const { data } = await backendApi.post("/reset-password", payload);
  return data;
};

/**
 * Cierra la sesión del usuario autenticado.
 *
 * Requiere el token CSRF de la sesión para validar la request en el backend.
 */
export const logoutUser = async (csrfToken: string): Promise<LogoutResponse> => {
  const { data } = await backendApi.post<LogoutResponse>(
    "/logout",
    {},
    { headers: { "X-CSRF-Token": csrfToken } }
  );
  return data;
};