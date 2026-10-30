<?php

namespace Database\Seeders;

use App\Enums\MessageTemplatePurpose;
use App\Models\Church;
use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Starter message templates (Swedish). Only adds what a church is missing, so edited templates are never overwritten.
 */
class MessageTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Church::query()->cursor() as $church) {
            self::seedForChurch($church);
        }
    }

    public static function seedForChurch(Church $church): void
    {
        foreach (self::templates() as $template) {
            $exists = MessageTemplate::query()
                ->withoutGlobalScope('church')
                ->where('church_id', $church->id)
                ->where(fn ($query) => $query->where('key', $template['key'])->orWhere('name', $template['name']))
                ->exists();

            if ($exists) {
                continue;
            }

            MessageTemplate::query()->create([
                ...$template,
                'church_id' => $church->id,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array{name: string, key: string, purpose: MessageTemplatePurpose, subject: string, body: string, text_body: string, whatsapp_template: string}>
     */
    public static function templates(): array
    {
        return [
            [
                'name' => 'Kräva svar',
                'key' => 'krava-svar',
                'purpose' => MessageTemplatePurpose::Service,
                'subject' => '{{church}}, {{task}},  {{event}},  {{date}}',
                'body' => <<<'HTML'
                    <p>Du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}. Var snäll och meddela oss om du kan eller inte kan komma och göra uppgiften igenom att trycka på en av knapparna nedan.</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>
                          <table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td align="center" bgcolor="#90EE90" style="border-radius: 6px; padding: 12px 24px;">
                                <a href="{{accept_url}}" target="_blank" style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; color: #14532D; text-decoration: none; display: block;">Ja, jag kommer</a>
                              </td>
                              <td width="12" style="font-size: 0; line-height: 0;">&nbsp;</td>
                              <td align="center" bgcolor="#FAA0A0" style="border-radius: 6px; padding: 12px 24px;">
                                <a href="{{decline_url}}" target="_blank" style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; color: #7F1D1D; text-decoration: none; display: block;">Nej, jag kommer inte</a>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                    HTML,
                'text_body' => <<<'TEXT'
                    Du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}. Var snäll och meddela oss om du kan eller inte kan komma.
                    Ja, jag kommer  {{accept_link}}
                    Nej, jag kommer inte {{decline_link}}
                    TEXT,
                'whatsapp_template' => 'krava_svar',
            ],
            [
                'name' => 'Påminnelse endast',
                'key' => 'paminnelse-endast',
                'purpose' => MessageTemplatePurpose::Service,
                'subject' => 'Påminnelse {{task}},  {{event}},  {{date}}',
                'body' => <<<'HTML'
                    <p>Detta är bara en påminnelse att du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}.</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    HTML,
                'text_body' => <<<'TEXT'
                    Detta är bara en påminnelse att du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}.
                    Tack och Gud Välsigna Dig!
                    TEXT,
                'whatsapp_template' => 'paminnelse',
            ],
            [
                'name' => 'Gudstjänst anpassat meddelande',
                'key' => 'custom_service_mess',
                'purpose' => MessageTemplatePurpose::Service,
                'subject' => '{{church}}, {{task}}, {{event}}, {{date}}, uppdatering',
                'body' => <<<'HTML'
                    <p>Detta är ett anpassat meddelande ifrån {{message_sender}} angående {{task}} på {{event}} {{date}}:</p>
                    <p>{{custom_mess}}</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    HTML,
                'text_body' => <<<'TEXT'
                    Detta är ett anpassat meddelande ifrån {{message_sender}} angående {{task}} på {{event}} {{date}}:

                    {{custom_mess}}

                    Tack och Gud Välsigna Dig!
                    TEXT,
                'whatsapp_template' => 'gudstjanst_anpassat_meddelande',
            ],
            [
                'name' => 'Team anpassat meddelande',
                'key' => 'team_custom_message',
                'purpose' => MessageTemplatePurpose::Team,
                'subject' => '{{team}} meddelande',
                'body' => <<<'HTML'
                    <p>Detta är ett anpassat meddelande ifrån {{message_sender}} till teamet {{team}}:</p>
                    <p>{{custom_mess}}</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    HTML,
                'text_body' => <<<'TEXT'
                    Detta är ett anpassat meddelande ifrån {{message_sender}} till teamet {{team}}:

                    {{custom_mess}}

                    Tack och Gud Välsigna Dig!
                    TEXT,
                'whatsapp_template' => 'team_anpassat_meddelande',
            ],
            [
                'name' => 'Föredragen meddelandekanal',
                'key' => 'preferred_messageing',
                'purpose' => MessageTemplatePurpose::Other,
                'subject' => "{{message_sender}} har ändrat din 'Föredragen meddelandekanal'",
                'body' => <<<'HTML'
                    <p>Hej {{first_name}}! {{message_sender}} har ändrat din föredragna meddelandekanal.</p>
                    <p>Du kommer att fortsätta att få meddelanden ifrån gudstjänstplaneringssystemet för {{church}} på detta sätt.</p>
                    HTML,
                'text_body' => <<<'TEXT'
                    Hej {{first_name}}! {{message_sender}} har ändrat din föredragna meddelandekanal.

                    Du kommer att fortsätta att få meddelanden ifrån gudstjänstplaneringssystemet för {{church}} på detta sätt.
                    TEXT,
                'whatsapp_template' => 'foredragen_meddelandekanal',
            ],
        ];
    }
}
