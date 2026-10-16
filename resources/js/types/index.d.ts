export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};

export interface Theme {
    primary_color: string;
    secondary_color: string;
    font_family: string;
}

export interface ScheduleEntry {
    id: number;
    starts_at: string;
    title: string;
    description: string | null;
}

/** Données d'un événement vues par les invités (Event::publicPayload). */
export interface PublicEvent {
    title: string;
    slug: string;
    type: string;
    type_label: string;
    event_date: string;
    location_name: string | null;
    location_address: string | null;
    welcome_message: string | null;
    theme_config: Theme;
    dress_code: string | null;
    map: { latitude: number; longitude: number; embed_url: string } | null;
    map_url: string | null;
    schedule: ScheduleEntry[];
}
