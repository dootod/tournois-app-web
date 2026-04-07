package com.example.mobile.api;

import android.content.Context;
import android.content.SharedPreferences;

import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // 10.0.2.2 = localhost pour émulateur Android
    public static final String BASE_URL = "http://10.0.2.2/tournois-app-web/api/public/api/";
    private static final String PREFS = "tournois_prefs";
    private static final String KEY_TOKEN = "api_token";
    private static final String KEY_ADHERENT_ID = "adherent_id";

    private static Retrofit retrofit;
    private static String currentToken;

    public static void init(Context ctx) {
        if (currentToken != null) return; // already set in memory, don't clobber from async-pending prefs
        SharedPreferences prefs = ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
        currentToken = prefs.getString(KEY_TOKEN, null);
    }

    public static void setToken(Context ctx, String token, Integer adherentId) {
        currentToken = token;
        SharedPreferences.Editor e = ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit();
        e.putString(KEY_TOKEN, token);
        if (adherentId != null) e.putInt(KEY_ADHERENT_ID, adherentId);
        e.apply();
        retrofit = null;
    }

    public static void clear(Context ctx) {
        currentToken = null;
        ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit().clear().apply();
        retrofit = null;
    }

    public static boolean isLogged() { return currentToken != null; }

    public static ApiService service() {
        if (retrofit == null) {
            HttpLoggingInterceptor log = new HttpLoggingInterceptor();
            log.setLevel(HttpLoggingInterceptor.Level.BODY);
            OkHttpClient.Builder client = new OkHttpClient.Builder()
                .addInterceptor(log)
                .addInterceptor(chain -> {
                    okhttp3.Request.Builder b = chain.request().newBuilder();
                    if (currentToken != null) {
                        b.header("Authorization", "Bearer " + currentToken);
                    }
                    return chain.proceed(b.build());
                });
            retrofit = new Retrofit.Builder()
                .baseUrl(BASE_URL)
                .client(client.build())
                .addConverterFactory(GsonConverterFactory.create())
                .build();
        }
        return retrofit.create(ApiService.class);
    }
}
